<?php
namespace SeanMorris\PressKit;
class Menu
{
	protected static $menus = [];
	protected
		$name
		, $link
		, $path
		, $weight = 0
		, $children = []
	;

	protected function __construct($name, $path, $link, $weight, $classes = [], $children = [])
	{
		$this->name    = $name;
		$this->path    = $path;
		$this->link    = $link;
		$this->classes = $classes;
		$this->weight  = $weight;

		if($children)
		{
			$this->add($children);
		}
	}

	public function add($children, $path = false, \SeanMorris\Access\User $user = null)
	{
		$sortChildren = function($a, $b)
		{
			$ak = $a;
			$bk = $b;

			$a = $this->children[$a];
			$b = $this->children[$b];

			$a = is_object($a) ? $a->weight : $ak;
			$b = is_object($b) ? $b->weight : $bk;

			if(!$a)
			{
				$a = $ak;
			}

			if(!$b)
			{
				$b = $bk;
			}

			if($a < $b)
			{
				return -1;
			}
			elseif($a > $b)
			{
				return 1;
			}

			return 0;
		};
		foreach($children as $childName => $childLink)
		{
			if(is_array($childLink))
			{
				$cLink = NULL;

				if(array_key_exists('_link', $childLink)
					&& $childLink['_link'] !== FALSE
				){
					$cLink = $childLink['_link'];
					unset($childLink['_link']);
				}

				$cWeight = 0;

				$cTitle = $childName;

				if(isset($childLink['_title']))
				{
					$cTitle = $childLink['_title'];
					unset($childLink['_title']);
				}

				if(isset($childLink['_weight']))
				{
					$cWeight = $childLink['_weight'];
					unset($childLink['_weight']);
				}

				$cClasses = [];

				if(isset($childLink['_classes']))
				{
					$cClasses = $childLink['_classes'];
					unset($childLink['_classes']);
				}

				if(isset($childLink['_access']))
				{
					if($childLink['_access'])
					{
						if($childLink['_access'] === FALSE)
						{
							continue;
						}

						if($childLink['_access'] !== TRUE)
						{
							if(!$user || !$user->hasRole($childLink['_access']))
							{
								continue;
							}
						}
					}
					else
					{
						continue;
					}

					unset($childLink['_access']);
				}

				$newPiece = new static(
					$cTitle
					, $path !== false ? $path : $this->path
					, $cLink
					, $cClasses
					, $cWeight
				);

				$newPiece->add($childLink, $path, $user);

				$appendedExisting = FALSE;

				foreach($this->children as $existingChild)
				{
					if(is_object($existingChild) && $childName == $existingChild->name)
					{
						$existingChild->append($newPiece);

						uksort(
							$existingChild->children
							, $sortChildren->bindTo($existingChild)
						);
						
						$appendedExisting = TRUE;
						break;
					}
				}

				if(!$appendedExisting)
				{
					$this->children[] = $newPiece;
				}

				uksort($this->children, $sortChildren->bindTo($this));
			}
			else
			{
				$this->children[] = [$childName => $childLink];
			}
		}
	}

	public function append($menu)
	{
		$otherChildren = $menu->children;
		foreach ($this->children as $child)
		{
			foreach ($otherChildren as $i => $otherChild)
			{
				if($child->name == $otherChild->name)
				{
					unset($otherChildren[$i]);
					$child->append($otherChild);
				}
			}
		}
		$this->children = array_merge($this->children, $otherChildren);
	}

	public function hasChildren()
	{
		return (bool)count($this->children);
	}

	public function name()
	{
		return $this->name;
	}

	public function path()
	{
		if($this->path)
		{
			return $this->path;
		}
		
		return '';
	}

	public function link()
	{
		return $this->link;
	}

	public function weight()
	{
		return $this->weight;
	}

	public function children()
	{
		return $this->children;
	}

	public function classes()
	{
		return $this->classes;
	}

	public static function reset($name = false)
	{
		if($name === false)
		{
			static::$menus = [];
			return;
		}

		unset(static::$menus[$name]);
	}

	public static function single($path = NULL, $link = NULL, $children = [])
	{
		return new static(NULL, $path, $link, $children);		
	}

	public static function get($name, $path = NULL, $link = NULL, $children = [], \SeanMorris\Access\User $user = NULL)
	{
		if(!isset(static::$menus[$name]))
		{
			static::$menus[$name] = new static($name, $path, $link, 0, $children);
		}

		return static::$menus[$name];
	}

	public function isActive($url)
	{
		if($this->path === $url)
		{
			return true;
		}

		return false;
	}
}
