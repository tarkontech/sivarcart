# Contribution Guide

Thank you for considering contributing to SivarCart Core! Please review the following guidelines before submitting a pull request.

For significant changes, please open an issue first so we can discuss the approach.

## Process

1. Fork the project
2. Create a new branch
3. Code, test, commit, and push
4. Open a pull request detailing your changes

## Guidelines

- Ensure the coding style passes by running `composer lint`.
- Send a coherent commit history, making sure each commit in your pull request is meaningful.
- You may need to [rebase](https://git-scm.com/book/en/v2/Git-Branching-Rebasing) to avoid merge conflicts.
- Please remember that we follow [SemVer](http://semver.org/).

## Setup

Clone your fork, then install the dev dependencies:

```bash
composer install
```

## Lint

Lint your code:

```bash
composer lint
```

## Tests

Run all tests:

```bash
composer test
```

## Commits

The repository follows [Conventional Commits](https://www.conventionalcommits.org/), the same
format `commitlint` validates in JavaScript projects. The message has to be a **single line**,
without body nor footer, and it can have up to **300 characters**:

```text
<type>(<scope>): <description>
```

The allowed types are `build`, `chore`, `ci`, `docs`, `feat`, `fix`, `perf`, `refactor`,
`revert`, `style` and `test`:

```bash
feat(orders): agrega el checkout de invitado
fix: corrige el total de la orden
chore!: renombra la migracion de pagos
```

The scope and the `!` marker of a breaking change are optional. Commits that git writes on
its own, such as the ones made with `git commit --fixup` or the merge commits, are not
validated.

### Git hooks

The rules above are enforced by the hooks in `.githooks`, which are written in PHP: the
repository does not need Node.js nor any JavaScript dependency. Install them once after
cloning:

```bash
composer hooks:install
```

The command points `core.hooksPath` to `.githooks`, so the hooks travel with the repository
instead of living in `.git/hooks`. From that moment on:

| Hook | What it does |
|---|---|
| `commit-msg` | Validates the message against the rules above and rejects the commit when it does not comply. |
| `pre-commit` | Checks the formatting of the staged PHP files with Pint. Run `composer lint` to fix them. |
| `pre-push` | Runs the whole suite (`composer test`) before the commits leave your machine. |

To remove them:

```bash
git config --unset core.hooksPath
```

