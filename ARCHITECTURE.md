# Architecture: pest

## Purpose

An elegant testing framework for PHP built on top of PHPUnit. Pest provides a fluent, closure-based API (`test()`, `it()`, `describe()`, `expect()`) that reduces boilerplate while retaining full PHPUnit compatibility.

## Directory Structure

```
src/
  Functions.php           — Global functions: test(), it(), describe(), beforeEach(), afterEach(), dataset(), expect()
  Kernel.php              — Bootstraps Pest: registers bootstrappers, runs the test suite
  Configuration.php       — Pest configuration (preset, test paths, coverage settings)
  Expectation.php         — Fluent expectation builder returned by expect()
  Expectations/
    Each_Expectation.php        — Iterates over array/iterable values for chained assertions
    Higher_Order_Expectation.php — Chains expectations on object properties/method results
    Opposite_Expectation.php    — Inverts the next expectation via ->not()
  PendingCalls/
    Test_Call.php         — Fluent builder for a single test; adds groups, datasets, skip, etc.
    Describe_Call.php     — Fluent builder for a describe block
    Before_Each_Call.php / After_Each_Call.php
  Factories/
    Test_Case_Factory.php        — Creates PHPUnit TestCase subclasses from closures
    Test_Case_Method_Factory.php — Generates test method metadata
  ArchPresets/
    Abstract_Preset.php   — Base for architecture presets
    Php.php, Laravel.php, Strict.php, Security.php, ...  — Bundled arch rule sets
  Bootstrappers/
    Boot_Files.php        — Discovers and loads Pest.php config and test files
    Boot_Subscribers.php  — Registers PHPUnit event subscribers
    ...
  Logging/
    TeamCity/             — TeamCity-compatible test result logging
  Exceptions/             — Domain-specific exceptions for Pest error conditions
  Support/
    Arr.php, Description.php
```

## Key Design Decisions

- **PHPUnit as engine**: Pest generates PHPUnit `TestCase` subclasses at runtime; it does not replace PHPUnit's execution model
- **Closure-based tests**: Test closures are wrapped in generated `TestCase` methods, enabling `$this` injection and data providers transparently
- **Fluent `expect()` API**: `expect($value)->toBe(...)` chains are implemented via `__call` on `Expectation`, delegating to PHPUnit assertions
- **Architecture testing**: The `arch()` function integrates with `pest-arch` to assert structural constraints on your codebase

## Extension Points

- Add custom expectation methods via `expect()->extend('myMatcher', fn() => ...)`  in `Pest.php`
- Create custom arch presets by extending `Abstract_Preset`
- Implement `Bootstrapper` to add custom boot logic

## Dependency Flow

```
Global functions (test, it, expect)
  → TestCallFactory → TestCaseFactory (generates PHPUnit classes)
  → Kernel::boot() → [Bootstrappers] → PHPUnit TestRunner
  → PHPUnit event subscribers → Logging (TeamCity / dot / etc.)
```
