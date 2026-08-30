# Universal Clean Code & Architecture Rules

You are working on a real production project. Write code that is **clean, maintainable, readable, testable, scalable, and easy for another developer to understand**.

## Core Rules

* Follow **Clean Architecture** and keep a clear separation of responsibilities.
* Keep the project structure organized, logical, and consistent.
* Follow SOLID, DRY, KISS, and separation of concerns.
* Prefer simple, readable solutions over clever or unnecessarily abstract code.
* **Do not over-engineer.** Do not introduce patterns, abstractions, interfaces, repositories, services, or layers unless they provide real value.
* Each class, method, and function should have **one clear responsibility**.
* Keep methods small and focused.
* Use meaningful, self-explanatory names.
* Avoid deeply nested conditions and complicated logic.
* Avoid duplicated business logic.
* Keep business logic out of controllers, views, routes, and infrastructure-specific code.
* Use appropriate design patterns only when they genuinely solve a problem.

## Project Structure

Organize the project into clear boundaries such as:

* Domain / Business Logic
* Application / Use Cases
* Infrastructure
* Presentation / HTTP / UI
* Shared / Support

Adapt the exact folder structure to the framework instead of blindly forcing a specific architecture.

The structure should make it immediately obvious:

**where business logic lives, where database logic lives, where HTTP/UI logic lives, and where external integrations live.**

## Database & Performance

Be extremely careful about performance.

* **N+1 queries are strictly forbidden.**
* Always inspect relationships before writing queries.
* Use eager loading when relationships are required.
* Avoid querying inside loops.
* Avoid unnecessary database queries.
* Select only the columns actually needed when appropriate.
* Use efficient joins, aggregates, pagination, and batch operations where appropriate.
* Consider database indexes for frequently filtered, sorted, or joined columns.
* Avoid loading huge datasets into memory unnecessarily.
* Do not solve performance problems with random caching; first identify the actual bottleneck.
* When writing complex queries, think about the generated SQL and query count.

## Code Quality

Before considering the implementation complete, review the code for:

1. Correctness
2. Readability
3. Maintainability
4. Security
5. Performance
6. Database query efficiency
7. N+1 problems
8. Duplication
9. Unnecessary abstractions
10. Proper separation of responsibilities

### Comments

Comments should be written in **English** and added only when they provide real value.

* Do not add comments everywhere.
* Do not use comments to explain obvious code.
* Prefer clear names and simple structure so the code explains itself.
* Add a comment only when explaining **why** something is necessary, documenting a non-obvious decision, clarifying complex business logic, or describing an important workaround or limitation.
* Keep comments concise, accurate, and up to date.
* Never use comments as a substitute for refactoring unclear or overly complex code.

## Important

Do not blindly rewrite existing code or create unnecessary files.

**First understand the existing architecture, conventions, and domain.** Reuse existing abstractions when they are good. Improve them only when necessary.

When implementing a feature, think like a senior production engineer:

> **Simple first. Clean always. Performant by design. Easy to change later.**

Before finishing, perform a mental code review and actively look for **N+1 queries, unnecessary database calls, duplicated logic, over-engineering, bad responsibilities, excessive or unhelpful comments, and performance bottlenecks.**
