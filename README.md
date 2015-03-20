# php-static-analyze

在普通的 PHP linter 之外提供增强的类型检查功能。

## 原理

我们可以知道字面量、内置函数返回值的类型，因此也可以知道表达式的返回类型。分析用户自定义函数的代码也能知道此函数的返回类型，通过对比类型，提示用户可能出bug的地方。

## 例子

```php
$a = trim($b);     // trim() always return string
if ($a === null) { // error, because $a's type is string, but null' type is null
    // ...
}
```
