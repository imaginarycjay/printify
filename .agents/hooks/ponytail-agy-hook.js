#!/usr/bin/env node
// Antigravity CLI lifecycle hook adapter for Ponytail
// Bridges Antigravity's SessionStart and PreInvocation hooks to Ponytail's runtime.

const fs = require('fs');
const path = require('path');
const { execFileSync } = require('child_process');
const { getDefaultMode, isDeactivationCommand, writeDefaultMode } = require('./ponytail-config');
const { getPonytailInstructions } = require('./ponytail-instructions');
const { clearMode, readMode, setMode } = require('./ponytail-runtime');

const event = process.argv[2] || 'SessionStart';
let input = '';
let done = false;

function writeJson(obj) {
  fs.writeSync(1, JSON.stringify(obj));
}

function handleSessionStart() {
  const mode = getDefaultMode();
  if (mode === 'off') {
    clearMode();
    writeJson({});
    return;
  }

  try {
    setMode(mode);
  } catch (e) {}

  let output = getPonytailInstructions(mode);

  if (mode !== 'review' && process.env.PONYTAIL_MAP !== '0') {
    try {
      const map = execFileSync(process.execPath, [path.join(__dirname, 'ponytail-map.js')], {
        encoding: 'utf8',
        timeout: 3000,
        maxBuffer: 64 * 1024,
        stdio: ['ignore', 'pipe', 'ignore'],
      }).trimEnd();
      if (map) output += '\n\n' + map;
    } catch (e) {}
  }

  writeJson({
    injectSteps: [
      {
        systemMessage: {
          systemMessage: output,
        },
      },
    ],
  });
}

function handlePreInvocation() {
  let data = {};
  try {
    data = JSON.parse(input.replace(/^\uFEFF/, ''));
  } catch (e) {}

  const prompt = String(data.lastUserInput || '').trim().toLowerCase();
  if (!prompt) {
    writeJson({});
    return;
  }

  if (/^[/@$]ponytail/.test(prompt)) {
    const parts = prompt.split(/\s+/);
    const cmd = parts[0].replace(/^[@$]/, '/');
    const arg = parts[1] || '';

    if (cmd === '/ponytail' || cmd === '/ponytail:ponytail') {
      if (arg === 'default') {
        const dmode = parts[2];
        if (dmode === 'off' || dmode === 'lite' || dmode === 'full' || dmode === 'ultra') {
          writeDefaultMode(dmode);
          writeJson({
            injectSteps: [
              {
                systemMessage: {
                  systemMessage: 'PONYTAIL DEFAULT SET — new sessions start in ' + dmode + '.',
                },
              },
            ],
          });
          return;
        }
      }

      let mode = null;
      let isReportOnly = false;
      if (arg === 'lite') mode = 'lite';
      else if (arg === 'full') mode = 'full';
      else if (arg === 'ultra') mode = 'ultra';
      else if (arg === 'off') mode = 'off';
      else if (arg === '') {
        const live = readMode();
        if (live && live !== 'off') {
          isReportOnly = true;
          mode = live;
        } else {
          mode = getDefaultMode() === 'off' ? 'full' : getDefaultMode();
        }
      }

      if (isReportOnly) {
        writeJson({
          injectSteps: [
            {
              ephemeralMessage: 'PONYTAIL MODE ACTIVE — level: ' + mode,
            },
          ],
        });
        return;
      }

      if (mode && mode !== 'off') {
        try {
          setMode(mode);
        } catch (e) {}
        const msg = 'PONYTAIL MODE CHANGED — level: ' + mode + '\n\n' + getPonytailInstructions(mode);
        writeJson({
          injectSteps: [
            {
              systemMessage: {
                systemMessage: msg,
              },
            },
          ],
        });
        return;
      }

      if (mode === 'off') {
        clearMode();
        writeJson({
          injectSteps: [
            {
              systemMessage: {
                systemMessage: 'PONYTAIL MODE OFF — revert to standard mode.',
              },
            },
          ],
        });
        return;
      }
    }
  }

  if (isDeactivationCommand(prompt)) {
    clearMode();
    writeJson({
      injectSteps: [
        {
          systemMessage: {
            systemMessage: 'PONYTAIL MODE OFF — revert to standard mode.',
          },
        },
      ],
    });
    return;
  }

  writeJson({});
}

function finish() {
  if (done) return;
  done = true;
  try {
    if (event === 'SessionStart') {
      handleSessionStart();
    } else if (event === 'PreInvocation') {
      handlePreInvocation();
    } else {
      writeJson({});
    }
  } catch (e) {
    try {
      writeJson({});
    } catch (_) {}
  }
}

process.stdin.on('data', (chunk) => {
  input += chunk;
});
process.stdin.on('end', () => {
  finish();
  process.exit(0);
});
process.stdin.on('error', () => {
  finish();
  process.exit(0);
});
setTimeout(() => {
  finish();
  process.exit(0);
}, 1000);
