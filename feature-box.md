# Feature box

## Dialect

| Implementation          | Syntax | Reified | Type arguments  | Variance              | Bound syntax                     | Bound enforced |
| ----------------------- | ------ | ------- | --------------- | --------------------- | -------------------------------- | -------------- |
| `phpdoc-impl`           | PHPDoc | ❌      | annotation only | `@template-covariant` | `@template T of I`               | ❌             |
| `reified-generics-impl` | Native | ✅      | `Foo::<T>`      | `out` / `in`          | `T : I`                          | ✅             |
| `holly-generics-impl`   | Native | ✅      | `Foo<T>`        | ❌                    | `T implements I` / `T extends C` | ✅             |

## Declaration sites

| Feature                                     | phpdoc | reified | holly      |
| ------------------------------------------- | ------ | ------- | ---------- |
| Generic interface                           | doc    | ✅      | ✅         |
| Generic class                               | doc    | ✅      | ✅         |
| Abstract generic class                      | doc    | ✅      | ✅         |
| `implements Iface<T>` parameter passthrough | doc    | ✅      | ✅         |
| `extends Base<T>` parameter passthrough     | doc    | ✅      | ✅         |
| Generic trait                               | doc    | ✅      | ❌         |
| Bounded type parameter                      | doc    | ✅      | ✅         |
| Type parameter default (`T = int`)          | ❌     | ✅      | ❌         |
| Variadic type parameter pack (`...Ts`)      | ❌     | ❌      | class only |

## Use sites

| Feature                                       | phpdoc | reified | holly |
| --------------------------------------------- | ------ | ------- | ----- |
| Generic free function                         | doc    | ✅      | ❌    |
| Generic method                                | doc    | ✅      | ✅    |
| Generic method on a generic class             | doc    | ✅      | ✅    |
| Static generic factory returning `C<U>`       | doc    | ✅      | ✅    |
| Statics / constants on an instantiation       | n/a    | ✅      | ✅    |
| `instanceof` an instantiation                 | n/a    | ✅      | ✅    |
| Variadic value parameter `T ...$xs`           | doc    | ✅      | ✅    |
| `T::class`                                    | ❌     | ❌      | ✅    |
| `self` return type inside a generic class     | ✅     | ✅      | ❌    |
| Type parameter nested in a type argument      | doc    | ✅      | ❌    |
| Explicit type arguments required at call site | n/a    | ✅      | ✅    |
