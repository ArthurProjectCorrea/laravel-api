---
applyTo: '**'
---
Provide project context and coding guidelines that AI should follow when generating code, answering questions, or reviewing changes.

## Commit Message Generation Guidelines

When generating commit messages, follow these steps to create semantic, informative commits based on staged files:

### 1. Analyze Staged Files
- Read all files currently in the Git staging area (`git status --porcelain` or equivalent)
- Examine the content of modified, added, or deleted files to understand the nature of changes
- Identify the primary purpose: new features, bug fixes, refactoring, documentation, etc.

### 2. Use Conventional Commit Format
Structure commit messages as: `type(scope): description`

**Common Types:**
- `feat`: New feature or functionality
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style/formatting changes (no logic changes)
- `refactor`: Code refactoring (no new features or fixes)
- `test`: Adding or modifying tests
- `chore`: Maintenance tasks, build changes, dependency updates
- `perf`: Performance improvements
- `ci`: CI/CD pipeline changes
- `build`: Build system or dependency changes

**Scope (optional):**
- Use module/component names like `auth`, `api`, `frontend`, `database`
- Only include if it helps clarify the change's impact

### 3. Create Descriptive Messages
- **Subject Line**: Keep under 50 characters, start with lowercase
- **Body** (optional): Provide more context, explain why and how
- **Footer** (optional): Reference issues, breaking changes

### 4. Context Synthesis
- Combine information from all staged files to create a cohesive message
- Prioritize the most significant change if multiple types are present
- Use imperative mood: "add", "fix", "update" (not "added", "fixed")

### 5. Examples
- `feat(auth): add user registration endpoint`
- `fix(api): resolve null pointer in user validation`
- `refactor(database): optimize query performance`
- `chore(deps): update Laravel to version 12`
- `docs(readme): update installation instructions`

### 6. Special Cases
- For multiple file types, choose the most appropriate primary type
- If changes span multiple areas, consider separate commits or use `chore` with detailed body
- Always ensure the message accurately reflects the staged changes