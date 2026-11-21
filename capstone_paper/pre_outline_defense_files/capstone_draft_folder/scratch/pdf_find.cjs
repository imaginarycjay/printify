const pdfjs = require('pdfjs-dist/legacy/build/pdf.js');
const fs = require('fs');
(async () => {
  const data = new Uint8Array(fs.readFileSync(process.argv[2]));
  const doc = await pdfjs.getDocument({ data }).promise;
  console.log('PAGES', doc.numPages);
  const out = [];
  for (let p = 1; p <= doc.numPages; p++) {
    const page = await doc.getPage(p);
    const tc = await page.getTextContent();
    let text = '', lastY = null;
    for (const it of tc.items) {
      if (lastY !== null && Math.abs(it.transform[5] - lastY) > 2) text += '\n';
      text += it.str + ' ';
      lastY = it.transform[5];
    }
    out.push('=====PAGE ' + p + '=====\n' + text);
  }
  fs.writeFileSync('pdf_text.txt', out.join('\n'));
})();
