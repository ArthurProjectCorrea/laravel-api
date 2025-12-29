---
agent: agent
---
Generate a semantic commit message based on the provided Git status output and staged file contents. Follow the guidelines in `.github/instructions/commit.instructions.md` for conventional commit format and analysis.

## Task Requirements:
1. **Parse Git Status**: Extract the list of staged files from the "Changes to be committed" section
2. **Read File Contents**: Access and analyze the content of each staged file to understand the nature of changes
3. **Classify Changes**: Determine the primary commit type (feat, fix, docs, style, refactor, test, chore, perf, ci, build) based on file modifications
4. **Identify Scope**: If applicable, determine a scope (e.g., auth, api, frontend) from the affected components
5. **Craft Message**: Create a concise, descriptive commit message following the format `type(scope): description`
6. **Context Synthesis**: Combine insights from all files into a cohesive message, prioritizing the most significant change

## Input Format:
The input will be in the format:
```
/commit it status
[git status output here]
```

## Output Format:
Provide the complete `git commit -m "message"` command with the generated semantic message. Include a brief explanation of the classification if the changes are complex.

## Constraints:
- Subject line must be under 50 characters
- Use imperative mood (add, fix, update)
- Only commit staged files as listed
- If multiple types apply, choose the most appropriate primary type
- Reference the commit instructions for examples and special cases

## Success Criteria:
- Commit message accurately reflects the staged changes
- Follows conventional commit standards
- Provides clear, actionable description of what was changed