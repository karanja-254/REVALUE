# Task Breakdown Template

Copy this template into a new file for each task. Fill it out with the orchestrator, then distribute subtasks to team members.

---

## Task: [Name]

**Context:**
- Project: [ReValue, feature branch, etc.]
- Language/Framework: [React, Laravel, Node, etc.]
- Constraints: [No new deps, 3-day deadline, mobile-only, etc.]

**Success Criteria:**
- [ ] [Measurable outcome 1]
- [ ] [Measurable outcome 2]
- [ ] [Tests pass / CI green]
- [ ] [Code reviewed and approved]

**Owner (Orchestrator):** [Name]  
**Created:** [Date]  
**Target Completion:** [Date]

---

## Subtask Breakdown

### Subtask 1: [Title]

**Agent Assignment:** [Opus / Sonnet / Haiku / GPT-4]  
**Effort:** [Xh]  
**Status:** 🔵 Ready / 🟡 Waiting / 🟢 In Progress / ✅ Done

**Dependencies:** [None / Subtask N / External]

**Description:**
[What needs to be done. Be specific.]

**Input Needed:**
- [File/spec/design from another subtask]
- [Context or decision from orchestrator]

**Output Deliverable:**
- [ ] [Specific code, file, doc, or PR]
- [ ] [Test coverage requirement]
- [ ] [Review/approval gate]

**Success Criteria:**
- [ ] [Specific, testable outcome]
- [ ] [Passes linting / type checking]

**Risks/Unknowns:**
- [Anything that could block this]
- [Design decision needed before starting?]

**Assigned To:** [Team member]  
**Started:** [Date]  
**Completed:** [Date]

---

### Subtask 2: [Title]

**Agent Assignment:** [Opus / Sonnet / Haiku / GPT-4]  
**Effort:** [Xh]  
**Status:** 🔵 Ready / 🟡 Waiting / 🟢 In Progress / ✅ Done

**Dependencies:** [Subtask 1]

**Description:**
[What needs to be done.]

**Input Needed:**
- [Subtask 1 output]
- [Schema / API contract from Subtask 1]

**Output Deliverable:**
- [ ] [Code file(s)]
- [ ] [Tests]
- [ ] [PR link]

**Success Criteria:**
- [ ] [Specific, testable outcome]

**Risks/Unknowns:**
- [Any open questions?]

**Assigned To:** [Team member]  
**Status Updates:** [Keep this live as work progresses]

---

### Subtask 3: [Title]

[Repeat template above]

---

## Execution Plan

**Critical Path:**
```
Subtask 1 (2h) 
  → Subtask 2 (3h)
  → Subtask 3 (1h)
  → Subtask 5 (2h) = 8h total
```

**Parallel Work:**
```
Subtask 1 (2h)
  ├→ Subtask 2 (3h)
  └→ Subtask 4 (2h) [can run while 2 is happening]
```

**Dependency Graph:**
- Subtask 1 blocks: 2, 3
- Subtask 2 blocks: 5
- Subtask 3 blocks: 5
- Subtask 4 independent
- Subtask 5 (integration) waits on: 2, 3

**Timeline:**
| Subtask | Start | End | Blocker | Owner |
|---------|-------|-----|---------|-------|
| 1 | Day 1 | Day 1 | None | [Name] |
| 2 | Day 1 | Day 2 | 1 | [Name] |
| 3 | Day 1 | Day 2 | 1 | [Name] |
| 4 | Day 1 | Day 2 | None | [Name] |
| 5 | Day 2 | Day 3 | 2, 3 | [Name] |

**Total Effort:** 10 person-hours  
**Total Duration:** 3 days (with parallelization)

---

## Integration & Validation

**Code Review Gate:**
- Agent: [GPT-4]
- Reviews: [Subtask outputs]
- Success: [All findings resolved]

**Testing Gate:**
- Unit tests: [Coverage %]
- Integration tests: [All pass]
- Manual testing: [Checklist]

**Final Checklist:**
- [ ] All subtasks complete
- [ ] Code review approved
- [ ] Tests pass on CI
- [ ] No new console warnings/errors
- [ ] Documentation updated
- [ ] Ready to merge

---

## Risk Summary

| Risk | Impact | Mitigation |
|------|--------|-----------|
| [Subtask 1 design decision unclear] | Blocks 2, 3 | Clarify with Opus before day 1 |
| [Subtask 2 API contract may change] | Cascades to 3, 5 | Lock API contract in review gate |
| [Performance unknown under load] | Rework needed | Add perf test to Subtask 4 |

**Escalation Trigger:**
If any subtask slips >2 hours, or a blocker is discovered, pause and escalate to orchestrator for re-planning.

---

## Communication

**Standups:**
- Daily: [Time] — all subtask owners report status
- Blocker: If stuck, tag orchestrator immediately

**Channels:**
- Slack: [Channel]
- PRs: Link to task breakdown in description
- Notes: Add comments below for async updates

---

## Post-Completion

**Lessons Learned:**
- What went well?
- What surprised you?
- Estimate vs. actual: [Subtask 1: 2h planned, 2.5h actual]
- Improvements for next task?

**Archive:** Link this task breakdown in project wiki / pinned Slack for future reference.

---

**Orchestrator Sign-Off:** [Name] — [Date]  
**All Subtasks Complete:** [Date]  
**Code Review Approved:** [Date]  
**Merged:** [Date]
