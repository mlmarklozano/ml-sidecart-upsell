<?php
/**
 * Core class for Side Cart Upsells.
 *
 * Responsibilities:
 *  - Register all WordPress/WooCommerce hooks.
 *  - Pull and filter upsell products from the current cart.
 *  - Render the upsell section HTML.
 *  - Return a WooCommerce AJAX cart fragment so the section updates
 *    without a page reload.
 *  - Enqueue CSS/JS assets.
 *
 * @package SideCartUpsells
 */

defined( 'ABSPATH' ) || exit;

class SCU_Upsells {

	/** @var SCU_Upsells|null Singleton instance. */
	private static $instance = null;

	/** Maximum number of upsell products to display. */
	const MAX_PRODUCTS = 4;

	/**
	 * Returns (and creates on first call) the singleton instance.
	 *
	 * @return SCU_Upsells
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/** Private constructor — use get_instance(). */
	private function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// Render upsells after the mini-cart item list (inside the non-empty cart block).
		add_action( 'woocommerce_after_mini_cart_contents', array( $this, 'render_upsells' ) );

		// Supply an updated HTML fragment each time the cart changes via AJAX.
		add_filter( 'woocommerce_add_to_cart_fragments', array( $this, 'cart_fragments' ) );
	}

	// -------------------------------------------------------------------------
	// Assets
	// -------------------------------------------------------------------------

	/**
	 * Enqueue plugin stylesheet and script on the front end.
	 * Assets are loaded on every page because the side cart can open anywhere.
	 */
	public function enqueue_assets() {
		wp_enqueue_style(
			'scu-upsells-style',
			SCU_URL . 'assets/css/scu-upsells.css',
			array(),
			SCU_VERSION
		);

		wp_enqueue_script(
			'scu-upsells-script',
			SCU_URL . 'assets/js/scu-upsells.js',
			array( 'jquery' ),
			SCU_VERSION,
			true  // Load in footer.
		);

		wp_localize_script(
			'scu-upsells-script',
			'scuData',
			array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'scu_nonce' ),
				'addedText'  => esc_html__( 'Added!', 'sidecart-upsells' ),
				'addText'    => esc_html__( 'Add to cart', 'sidecart-upsells' ),
			)
		);
	}

	// -------------------------------------------------------------------------
	// WooCommerce AJAX fragment
	// -------------------------------------------------------------------------

	/**
	 * Return the upsell block as a WooCommerce cart fragment.
	 *
	 * The array key must match a unique CSS selector present in the DOM so
	 * WooCommerce's JS can find and replace the element after each cart update.
	 *
	 * @param  array $fragments Existing fragments array.
	 * @return array
	 */
	public function cart_fragments( $fragments ) {
		ob_start();
		$this->render_upsells();
		$fragments['.scu-upsells'] = ob_get_clean();
		return $fragments;
	}

	// -------------------------------------------------------------------------
	// Rendering
	// -------------------------------------------------------------------------

	/**
	 * Output the full upsell section.
	 *
	 * Always emits a `.scu-upsells` wrapper so WooCommerce fragments can
	 * target it even when there are no products to show.
	 */
	public function render_upsells() {
		$products = $this->get_upsell_products();

		if ( empty( $products ) ) {
			// Empty wrapper keeps the fragment key present in the DOM.
			echo '<div class="scu-upsells" hidden></div>';
			return;
		}
		?>
		<div class="scu-upsells">
			<h4 class="scu-upsells__title">
				<?php esc_html_e( "Don't miss out", 'sidecart-upsells' ); ?>
			</h4>
			<div class="scu-upsells__grid">
				<?php foreach ( $products as $product ) : ?>
					<?php $this->render_item( $product ); ?>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Output a single upsell product card.
	 *
	 * Simple products get an AJAX add-to-cart button (using WooCommerce's own
	 * `ajax_add_to_cart` mechanism). Variable products link to their product
	 * page instead, as variations must be selected first.
	 *
	 * @param WC_Product $product
	 */
	private function render_item( WC_Product $product ) {
		$is_variable  = $product->is_type( 'variable' );
		$product_url  = $product->get_permalink();
		$product_name = $product->get_name();
		?>
		<div class="scu-upsells__item">

			<a class="scu-upsells__img-link" href="<?php echo esc_url( $product_url ); ?>" tabindex="-1" aria-hidden="true">
				<?php
				echo wp_kses_post(
					$product->get_image(
						'woocommerce_thumbnail',
						array( 'class' => 'scu-upsells__img' )
					)
				);
				?>
			</a>

			<div class="scu-upsells__info">

				<a class="scu-upsells__name" href="<?php echo esc_url( $product_url ); ?>">
					<?php echo esc_html( $product_name ); ?>
				</a>

				<span class="scu-upsells__price">
					<?php echo wp_kses_post( $product->get_price_html() ); ?>
				</span>

				<?php if ( $is_variable ) : ?>
					<a class="scu-upsells__select-btn"
					   href="<?php echo esc_url( $product_url ); ?>">
						<?php esc_html_e( 'Select options', 'sidecart-upsells' ); ?>
					</a>
				<?php else : ?>
					<button
						class="scu-upsells__add-btn ajax_add_to_cart add_to_cart_button"
						type="button"
						data-product_id="<?php echo esc_attr( $product->get_id() ); ?>"
						data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>"
						data-quantity="1"
						aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name */ __( 'Add "%s" to your cart', 'sidecart-upsells' ), $product_name ) ); ?>"
						rel="nofollow">
						<?php esc_html_e( 'Add to cart', 'sidecart-upsells' ); ?>
					</button>
				<?php endif; ?>

			</div><!-- .scu-upsells__info -->

		</div><!-- .scu-upsells__item -->
		<?php
	}

	// -------------------------------------------------------------------------
	// Product logic
	// -------------------------------------------------------------------------

	/**
	 * Build the list of upsell products to display.
	 *
	 * Process:
	 *  1. Collect upsell IDs from every product currently in the cart.
	 *  2. Deduplicate.
	 *  3. Exclude products already in the cart.
	 *  4. Filter to purchasable, in-stock products.
	 *  5. Cap at MAX_PRODUCTS.
	 *
	 * @return WC_Product[] Ordered, filtered list of upsell products.
	 */
	private function get_upsell_products() {
		$cart = WC()->cart;

		if ( ! $cart || $cart->is_empty() ) {
			return array();
		}

		$cart_product_ids = array();
		$upsell_ids       = array();

		foreach ( $cart->get_cart() as $item ) {
			$cart_product_ids[] = (int) $item['product_id'];

			$product = wc_get_product( $item['product_id'] );
			if ( $product ) {
				$upsell_ids = array_merge( $upsell_ids, $product->get_upsell_ids() );
			}
		}

		// Deduplicate and remove any IDs already in the cart.
		$upsell_ids = array_unique( $upsell_ids );
		$upsell_ids = array_diff( $upsell_ids, $cart_product_ids );

		$products = array();

		foreach ( $upsell_ids as $id ) {
			if ( count( $products ) >= self::MAX_PRODUCTS ) {
				break;
			}

			$product = wc_get_product( $id );

			if ( ! $product ) {
				continue;
			}

			if ( ! $product->is_purchasable() || ! $product->is_in_stock() ) {
				continue;
			}

			$products[] = $product;
		}

		return $products;
	}
}
