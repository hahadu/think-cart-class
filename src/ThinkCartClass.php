<?php

/**
 * Cart: A very simple PHP cart library.
 *
 * Copyright (c) 2017 Sei Kan
 *
 * Distributed under the terms of the MIT License.
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright  2017 Sei Kan <seikan.dev@gmail.com>
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 * @see       https://github.com/seikan/Cart
 */
namespace Hahadu\ThinkCartClass;
use think\facade\Cookie;
use think\facade\Session;
use think\facade\Request;
class ThinkCartClass
{
	/**
	 * An unique ID for the cart.
	 *
	 * @var string
	 */
	protected $cartId;

	/**
	 * 购物车最大列数量.
	 *
	 * @var int
	 */
	protected $cartMaxItem = 0;

	/**
	 * 购物车中允许的最大商品数量.
	 *
	 * @var int
	 */
	protected $itemMaxQuantity = 0;

	/**
	 * 设置cookie.
	 *
	 * @var bool
	 */
	protected $cartCookie = false;

	/**
	 * 购物车所以列.
	 *
	 * @var array
	 */
	private $items = [];

	/**
	 * 初始化购物车类.
	 *
	 * @param array $options
	 */
	public function __construct($options = [])
	{

		if (isset($options['cartMaxItem']) && preg_match('/^\d+$/', $options['cartMaxItem'])) {
			$this->cartMaxItem = $options['cartMaxItem'];
		}

		if (isset($options['itemMaxQuantity']) && preg_match('/^\d+$/', $options['itemMaxQuantity'])) {
			$this->itemMaxQuantity = $options['itemMaxQuantity'];
		}

		if (isset($options['cartCookie']) && $options['cartCookie']) {
			$this->cartCookie = true;
		}
		if(isset($options['cartId']) && $options['cartId']){
		    $this->cartId = 'cartId_'.$options['cartId'];
        }else{
            $this->cartId = 'cartId_'.md5((Request::has('HTTP_HOST','server')) ? Request::server('HTTP_HOST') : 'CooleCartClass') ;
        }


		$this->read();
	}

	/**
	 * 获取购物车列表.
	 *
	 * @return array
	 */
	public function getItems()
	{
		return $this->items;
	}

	/**
	 * 检查购物车是否空.
	 *
	 * @return bool
	 */
	public function isCartEmpty()
	{
		return empty(array_filter($this->items));
	}

	/**
	 * 单个商品数量.
	 *
	 * @return int
	 */
	public function getTotalItem()
	{
		$total = 0;
		foreach ($this->items as $items) {
			foreach ($items as $item) {
				++$total;
			}
		}
		return $total;
	}

	/**
	 * 购物车中商品数量的总和.
	 *
	 * @return int
	 */
	public function getTotalQuantity()
	{
		$quantity = 0;

		foreach ($this->items as $items) {
			foreach ($items as $item) {
				$quantity += $item['quantity'];
			}
		}

		return $quantity;
	}

	/**
	 * 商品sku数量.
	 *
	 * @param string $attribute
	 *
	 * @return int
	 */
	public function getAttributeTotal($attribute = 'price')
	{
		$total = 0;

		foreach ($this->items as $items) {
			foreach ($items as $item) {
				if (isset($item['attributes'][$attribute])) {
					$total += $item['attributes'][$attribute] * $item['quantity'];
				}
			}
		}
		return $total;
	}

	/**
	 * 清空购物车.
	 */
	public function clear()
	{
		$this->items = [];
		$this->write();
	}

	/**
	 * 检查购物车是否为空.
	 *
	 * @param string $id
	 * @param array  $attributes
	 *
	 * @return bool
	 */
	public function isItemExists($id, $attributes = [])
	{
		$attributes = (is_array($attributes)) ? array_filter($attributes) : [$attributes];

		if (isset($this->items[$id])) {
			$hash = md5(json_encode($attributes));
			foreach ($this->items[$id] as $item) {
				if ($item['hash'] == $hash) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * 添加商品到购物车.
	 *
	 * @param string $id
	 * @param int    $quantity
	 * @param array  $attributes
	 *
	 * @return bool
	 */
	public function add($id, $quantity = 1, $attributes = [])
	{
		$quantity = (preg_match('/^\d+$/', $quantity)) ? $quantity : 1;
		$attributes = (is_array($attributes)) ? array_filter($attributes) : [$attributes]; //过滤数组中的元素
		$hash = md5(json_encode($attributes));

		if (count($this->items) >= $this->cartMaxItem && $this->cartMaxItem != 0) {
			return false;
		}

		if (isset($this->items[$id])) {
			foreach ($this->items[$id] as $index => $item) {
				if ($item['hash'] == $hash) {
					$this->items[$id][$index]['quantity'] += $quantity;
					$this->items[$id][$index]['quantity'] = ($this->itemMaxQuantity < $this->items[$id][$index]['quantity'] && $this->itemMaxQuantity != 0) ? $this->itemMaxQuantity : $this->items[$id][$index]['quantity'];

					$this->write();

					return true;
				}
			}
		}

		$this->items[$id][] = [
			'id'         => $id,
			'quantity'   => ($quantity > $this->itemMaxQuantity && $this->itemMaxQuantity != 0) ? $this->itemMaxQuantity : $quantity,
			'hash'       => $hash,
			'attributes' => $attributes,
		];

		$this->write();

		return true;
	}

	/**
	 * 更新购物车商品数量.
	 *
	 * @param string $id
	 * @param int    $quantity
	 * @param array  $attributes
	 *
	 * @return bool
	 */
	public function update($id, $quantity = 1, $attributes = [])
	{
		$quantity = (preg_match('/^\d+$/', $quantity)) ? $quantity : 1;

		if ($quantity == 0) {
			$this->remove($id, $attributes);

			return true;
		}

		if (isset($this->items[$id])) {
			$hash = md5(json_encode(array_filter($attributes)));

			foreach ($this->items[$id] as $index => $item) {
				if ($item['hash'] == $hash) {
					$this->items[$id][$index]['quantity'] = $quantity;
					$this->items[$id][$index]['quantity'] = ($this->itemMaxQuantity < $this->items[$id][$index]['quantity'] && $this->itemMaxQuantity != 0) ? $this->itemMaxQuantity : $this->items[$id][$index]['quantity'];
					$this->write();

					return true;
				}
			}
		}

		return false;
	}

	/**
	 * 删除单个商品.
	 *
	 * @param string $id
	 * @param array  $attributes
	 *
	 * @return bool
	 */
	public function remove($id, $attributes = [])
	{
		if (!isset($this->items[$id])) {
			return false;
		}

		if (empty($attributes)) {
			unset($this->items[$id]);

			$this->write();

			return true;
		}
		$hash = md5(json_encode(array_filter($attributes)));

		foreach ($this->items[$id] as $index => $item) {
			if ($item['hash'] == $hash) {
				unset($this->items[$id][$index]);

				$this->write();

				return true;
			}
		}

		return false;
	}

	/**
	 * 删除购物车cookie.
	 */
	public function destroy()
	{
		$this->items = [];

		if ($this->cartCookie) {
            Cookie::delete($this->cartId);
		} else {
            Session::delete($this->cartId);
		}
	}

	/**
	 * 读取购物车session.
	 */
	private function read()
	{

		$this->items = ($this->cartCookie) ? json_decode((Cookie::has($this->cartId)) ? Cookie::get($this->cartId) : '[]', true) : json_decode((Session::has($this->cartId)) ? Session::get($this->cartId) : '[]', true);
	}

	/**
	 * 将更改写入购物车session.
	 */
	private function write()
	{
		if ($this->cartCookie) {
            Cookie::set($this->cartId, json_encode(array_filter($this->items)), time() + 604800);
		} else {
            Session::set($this->cartId, json_encode(array_filter($this->items)));
		}
	}
}
