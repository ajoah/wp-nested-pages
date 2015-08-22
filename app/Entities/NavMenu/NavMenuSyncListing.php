<?php namespace NestedPages\Entities\NavMenu;

use NestedPages\Entities\NavMenu\NavMenuSync;
use NestedPages\Helpers;
use NestedPages\Entities\Post\PostDataFactory;

/**
* Syncs the Generated Menu to Match the Listing
*/
class NavMenuSyncListing extends NavMenuSync implements NavMenuSyncInterface {


	/**
	* Individual Post
	* @var array
	*/
	private $post;

	/**
	* Menu Position Count
	* @var int
	*/
	private $count = 0;

	/**
	* Post Data Factory
	*/
	private $post_factory;

	public function __construct()
	{
		parent::__construct();
		$this->post_factory = new PostDataFactory;
	}

	/**
	* Create the menu with nested pages
	* Recursive function loops through child posts
	*/
	public function sync($parent = 0, $menu_parent = 0)
	{
		$this->count = $this->count + 1;
		$page_q = new \WP_Query(array(
			'post_type' => array('page','np-redirect'),
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'orderby' => 'menu_order',
			'order' => 'ASC',
			'post_parent' => $parent
		));
		if ( $page_q->have_posts() ) : while ( $page_q->have_posts() ) : $page_q->the_post();
			global $post;
			$this->post = $this->post_factory->build($post);
			$this->syncItem($menu_parent);			
		endwhile; endif; wp_reset_postdata();
	}

	/**
	* Sync an individual item
	* @since 1.3.4
	*/
	private function syncItem($menu_parent)
	{
		// Get the Menu Item ID using the post ID
		$menu_item_id = $this->nav_menu_repo->getMenuItemID($this->post->id);

		if ( $this->post->nav_status == 'hide' ) return $this->removeItem($menu_item_id);
		$menu = $this->syncLinkItem($menu_parent, $menu_item_id);
		$this->sync( $this->post->id, $menu );
	}

	/**
	* Sync Link Menu Item
	* @since 1.1.4
	*/
	private function syncLinkItem($menu_parent, $menu_item_id)
	{
		$type = ( $this->post->nav_type ) ? $this->post->nav_type : 'custom';
		$object = ( $this->post->nav_object ) ? $this->post->nav_object : 'custom';
		$object_id = ( $this->post->nav_object_id  ) ? $this->post->nav_object_id : $this->post->id;
		$url = ( $type == 'custom' ) ? esc_url($this->post->content) : null;
		
		// Compatibility for 1.4.1 - Reset Page links
		if ( $type == 'custom' && $this->post->type == 'page' ){
			$type = 'post_type';
			$object = 'page';
			$object_id = $this->post->id;
		}

		$menu = wp_update_nav_menu_item($this->id, $menu_item_id, array(
			'menu-item-title' => $this->post->title,
			'menu-item-position' => $this->count,
			'menu-item-url' => $url,
			'menu-item-attr-title' => $this->post->nav_title_attr,
			'menu-item-status' => 'publish',
			'menu-item-classes' => $this->post->nav_css,
			'menu-item-type' => $type,
			'menu-item-object' => $object,
			'menu-item-object-id' => $object_id,
			'menu-item-parent-id' => $menu_parent,
			'menu-item-xfn' => $this->post->id,
			'menu-item-target' => $this->post->link_target
		));
		return $menu;
	}


}