# think-cart-class
think-cart-class是一个基于thinkphp6的购物车类


安装 composer require hahadu/think-cart-class

使用: 

```
<?php

$cart = new Hahadu\ThinkCartClass\ThinkCartClass([
            'cartCookie' => true, //是否开启cookie ，默认为false 关闭cookie时将自动选择session

         //   'cartId' => md5($username.Request::server('http_host')), //设置购物车id 会自动加上'cartId_'前缀，
         //前缀可在项目配置文件‘cart.php’中设置

]);

$cart->getTotalItem()获取购物车商品数量

$cart->isCartEmpty()  //检查购物车是否为空

$cart->add('商品id','数量',[属性]) //添加商品

$cart->remove('商品id',[属性]) //删除单条

$cart->update('商品id','数量',[属性]) //修改购物车商品数量

$cart->getItems() //列出购物车里所有商品

$cart->clear();  //清空购物车 

```
配置文件：在您的项目配置文件config目录中创建cart.php文件
```
//cart.php文件
return[
  'cart_prefix' = 'youp prefix' //设置购物车前缀 默认为'cartId_'

]

```
