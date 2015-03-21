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

## 函数的返回值

在PHP中，一个函数如果不使用return语句或者return语句后面没有表达式，则函数的返回值是空值。

```php
function foo() {return trim('3');}
function bar() {return;}
function baz() {}
```

上面的三个函数，foo()返回的是string，bar()和baz()返回的是null

上述是容易判断的情况，判断的原则是：如果有return语句，使用return语句的类型，如无，使用null。

```php
function opt() {
    if (expr) {
        return 1;
    }
}
```

上述情况下：如主流程无return语句，则可能返回null。

## 变量的可能类型

变量的可能类型是其被赋值时所有表达式的可能类型的并集

但list关键字需要特殊处理

## 表达式的可能类型

表达式的可能类型是由以下三种决定：

1. 运算符 如 . 一定返回字符串
2. 函数的返回值
3. 变量的可能类型

## 参数的可能类型

参数的可能类型

1. type hints
2. 被调用时实参的可能类型

## 有环图

大家可以很容易的看出来，上面是个闭环。不过，幸好线性方程是可解的。

## 数组内的值类型

以下所有可能的值的集合

1. 初始化
2. $a[] = 
3. array_push, array_unshift(), array_splice

## 对象的属性类型

1. $obj->prop 赋值
2. __set()

