# Agent Registry

Quick reference for assigning tasks to the right agent/model. Use with the Task Orchestrator prompt.

---

## Claude Opus 5
**Strengths:** Complex design, architecture, root-cause debugging, edge cases  
**Speed:** Slower (best quality)  
**Cost:** Highest  
**Use for:**
- System design, schema, API contracts
- Tricky bugs, race conditions, performance mysteries
- Breaking change decisions
- Security audits

**Avoid:** Mechanical coding, simple CRUD, straightforward PRs (overkill)

---

## Claude Sonnet 5
**Strengths:** Balanced design + implementation, migrations, config changes  
**Speed:** Medium  
**Cost:** Medium  
**Use for:**
- Database migrations, infrastructure changes
- Config refactors that touch multiple files
- Documentation for complex features
- Code review on less critical paths

**Avoid:** Pure architecture (use Opus), pure implementation (use Haiku)

---

## Claude Haiku 4.5
**Strengths:** Fast implementation, straightforward coding, unit tests  
**Speed:** Fastest  
**Cost:** Lowest  
**Use for:**
- Feature implementation (logic is locked in)
- CRUD endpoints, business logic
- Unit tests, integration tests
- Bug fixes (when root cause is known)
- Refactoring (when direction is set)

**Avoid:** Architecture decisions, tricky unknowns (needs Opus first)

---

## GPT-4 / GPT-4o
**Strengths:** Cross-file consistency, integration validation, security review  
**Speed:** Medium–fast  
**Cost:** Medium  
**Use for:**
- Code review (independent correctness check)
- API contract validation
- Security audits, OWASP checks
- Integration testing
- Breaking change impact analysis

**Avoid:** Initial design (use Opus), pure implementation (use Haiku)

---

## Selection Decision Tree

```
Is this a design/architecture decision?
├─ YES → Opus
│        (schema, system design, security model, breaking changes)
└─ NO → Does it involve unknowns or tricky debugging?
        ├─ YES → Opus
        │        (race conditions, performance mysteries, edge cases)
        └─ NO → Is this mechanical coding with clear requirements?
                ├─ YES → Haiku
                │        (implement feature, write tests, straightforward code)
                └─ NO → Is this a review/validation gate?
                        ├─ YES → GPT-4
                        │        (code review, security audit, integration check)
                        └─ NO → Sonnet
                                (migrations, config refactors, documentation)
```

---

## Team Workflow Example

**Monday: Task arrives**
1. Orchestrator (Claude Opus) decomposes into subtasks
2. Assign Opus → design subtask
3. Assign Haiku → impl subtasks (can work in parallel)
4. Haiku blocks on Opus design output

**Wednesday: Code ready for review**
1. Assign GPT-4 → code review
2. Assign Haiku → fix findings from review
3. Code review gates merge

**Friday: Integration**
1. Orchestrator validates all subtasks integrated
2. Deploy

---

## Cost Optimization

**Spend Opus tokens on:** Design, architecture, unknowns (high ROI)  
**Spend Haiku tokens on:** Implementation, straightforward code (low cost, high throughput)  
**Spend GPT-4 tokens on:** Review gates, validation (ensures no rework)

**Waste:**
- Haiku on design decisions (output is fragile; Opus rework needed)
- Opus on mechanical coding (overkill; Haiku is 10× cheaper)
- Skipping review (saves GPT-4 cost, but bugs cost Opus debugging time later)

---

## For Your ReValue Project

**Common assignments:**
- **Product features:** Opus (design schema) → Haiku (implement) → GPT-4 (review)
- **Bug fix:** Opus (diagnose) → Haiku (fix) or Opus (fix if tricky)
- **Migration:** Sonnet (plan) → Haiku (code) → GPT-4 (validate)
- **API design:** Opus (contract) → Haiku (endpoint impl) → GPT-4 (integration test)

---

## Agent Catalog (Extended)

If using Cursor's full model ecosystem:

| Agent | Best For | Speed | Cost | Go-To? |
|-------|----------|-------|------|--------|
| Claude Opus | Design, hard bugs | Slow | $$$ | YES (for risk) |
| Claude Sonnet | Balanced | Medium | $$ | Maybe (migrations) |
| Claude Haiku | Implementation | Fast | $ | YES (for speed) |
| GPT-4 | Review, validation | Medium | $$ | YES (for gates) |
| GPT-4o | Fast review | Fast | $ | Alternative to GPT-4 |

Pick the right tool. Haiku 10 times cheaper than Opus for the same implementation task.
