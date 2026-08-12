---
name: scholarly
description: "Academic paper and thesis writing assistant - Guides users through Q&A to progressively build all sections of a research paper (Introduction, Literature Review, Methods, Results, Discussion, Conclusion). Works with any AI agent framework (OpenClaw, Claude Code, Cursor, etc.). Trigger when users want to write academic papers, theses (bachelor/masters/PHD), journal articles, research reports, or mention paper outlines, literature review, research methods, paper structure, Abstract, Introduction, Method, Results, Discussion, Conclusion."
agent_created: true
---

# Scholarly - 学术论文写作助手

## 核心功能

通过引导式问答，帮助用户逐步构建学术论文/学位论文的所有核心章节：Title、Abstract、Introduction、Literature Review、Methods、Results、Discussion、Conclusion。支持从课程论文到博士论文的各个层次。

## 工作流程

### 第一阶段：确认论文基础信息

首先了解论文的基本情况：

1. **论文类型**：课程论文 / 期刊文章 / 会议论文 / 学位论文（本科/硕士/博士）
2. **学科领域**：明确所属学科（心理学、社会学、语言学、自然科学等）
3. **研究类型**：实证研究 / 文献综述 / 理论论文 / 方法论论文
4. **字数要求**：了解目标长度或页数要求
5. **引用格式**：APA / MLA / Chicago / Harvard / 其他

### 第二阶段：逐章构建

每个章节按以下流程推进：

**1. Title & Abstract（标题与摘要）**
- 先确定标题（简洁、包含关键词）
- 再写摘要（250字以内，涵盖研究问题、方法、主要发现、结论）

**2. Introduction（引言）**
- 逐步构建引言的四个层次：
  - 研究背景（broad context）
  - 研究重要性（why it matters）
  - 文献综述入口（what's known/unknown）
  - 研究目标与问题（what this paper does）

**3. Literature Review（文献综述）**
- 收集已阅读的关键文献
- 梳理研究脉络（chronological / thematic / methodological）
- 识别研究空白（gap）
- 定位本研究的理论框架

**4. Methods（方法）**
- 根据研究类型细化：
  - 实证量化：参与者、设计、材料、程序、统计分析
  - 实证质性：参与者、数据收集方法、分析方法、伦理
  - 文献综述：搜索策略、筛选标准、分析框架

**5. Results（结果）**
- 确定报告哪些分析
- 设计结果呈现方式（文字 / 表格 / 图形）
- 练习结果叙述（用过去时，客观描述）

**6. Discussion（讨论）**
- 解读结果（回应研究问题）
- 与已有研究对话（一致/矛盾/扩展）
- 承认局限性（limitations）
- 提出未来方向（future directions）

**7. Conclusion（结论）**
- 提炼核心贡献（key takeaways）
- 强调研究意义（broader implications）
- 避免引入新信息

### 第三阶段：迭代优化

- 检查各章之间的衔接
- 确保逻辑连贯性
- 完善引用和参考文献

## 各章节输出模板

### Title
```
[简洁描述核心变量和研究类型，15词以内]
Example: "The Effect of Working Memory Training on Reading Comprehension in Children"
```

### Abstract（250词以内）
```
背景（1-2句）→ 研究问题（1句）→ 方法（2-3句）→ 主要发现（2-3句）→ 结论（1-2句）
```

### Introduction 结构
```markdown
1. 开场与背景（2-3段）
   - 研究主题的重要性
   - 领域内的基本共识

2. 文献回顾（3-5段）
   - 按主题/时间/方法组织
   - 识别gap

3. 本研究（2-3段）
   - 研究问题/假设
   - 研究概述
```

### Methods 子结构
```markdown
## Participants
## Materials/Stimuli
## Procedure
## Data Analysis
```

### Results 叙述原则
- 用过去时
- 先报告主要分析，再报告次要
- 报告统计量：t(df)=x.xx, p=.xxx, d=x.xx

### Discussion 结构
```markdown
1. 主要发现回顾（1-2段）
2. 与文献对话（2-3段）
3. 理论/实践意义（1-2段）
4. 局限性（1段）
5. 未来方向（1段）
6. 简短结论（1段）
```

## 参考资料

详细的各章节写作指南、常见错误、学科特定建议，请参考：
- `references/academic_paper_templates.md`

## 重要原则

1. **从整体到局部**：先确定论文框架和研究问题，再逐章填充
2. **迭代写作**：不要试图一次写完，每章多次迭代
3. **读者视角**：假设读者是领域内的非专家，每章自成一个小的完整故事
4. **诚实呈现**：准确报告方法，不夸大结果，坦诚局限性
5. **使用中文输出**：除非用户要求使用英文
