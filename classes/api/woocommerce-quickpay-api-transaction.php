<?php
/**
 * WC_QuickPay_API_Transaction class
 * 
 * Used for common methods shared between payments and subscriptions
 *
 * @class 		WC_QuickPay_API_Payment
 * @since		4.0.0
 * @package		Woocommerce_QuickPay/Classes
 * @category	Class
 * @author 		PerfectSolution
 * @docs        http://tech.quickpay.net/api/services/?scope=merchant
 */

class WC_QuickPay_API_Transaction extends WC_QuickPay_API
{
    /**
	* get_current_type function.
	* 
	* Returns the current payment type
	*
	* @access public
	* @return void
	*/ 
    public function get_current_type() 
    {
    	$last_operation = $this->get_last_operation();
		
        if( ! is_object( $last_operation ) ) 
        {
            throw new QuickPay_API_Exception( "Malformed operation response", 0 ); 
        }
        
        return $last_operation->type;
    }


  	/**
	* get_last_operation function.
	* 
	* Returns the last successful transaction operation
	*
	* @access public
	* @return void
	* @throws QuickPay_API_Exception
	*/ 
	public function get_last_operation() 
	{
		if( ! is_object( $this->resource_data ) ) 
		{
			throw new QuickPay_API_Exception( 'No API payment resource data available.', 0 );
		}

		// Loop through all the operations and return only the operations that were successful (based on the qp_status_code and pending mode).
		$successful_operations = array_filter($this->resource_data->operations, function( $operation ) {
			return $operation->qp_status_code == 20000 || $operation->pending == TRUE;
		} );
        
        $last_operation = end( $successful_operations );
        
        if( $last_operation->pending == TRUE ) {
            $last_operation->type = __( 'Pending - check your QuickPay manager', 'woo-quickpay' );   
        }
        
		return $last_operation;
	}

    
    /**
	* is_test function.
	* 
	* Tests if a payment was made in test mode.
	*
	* @access public
	* @return boolean
	* @throws QuickPay_API_Exception
	*/     
    public function is_test() 
    {
		if( ! is_object( $this->resource_data ) ) {
			throw new QuickPay_API_Exception( 'No API payment resource data available.', 0 );
		}

    	return $this->resource_data->test_mode;
    }
    
   	/**
	* create function.
	* 
	* Creates a new payment via the API
	*
	* @access public
	* @param  WC_QuickPay_Order $order
	* @return object
	* @throws QuickPay_API_Exception
	*/   
    public function create( WC_QuickPay_Order $order ) 
    {
        $base_params = array(
            'currency' => WC_QP()->get_gateway_currency( $order ),
            'order_post_id' => version_compare( WC_VERSION, '3.0', '<' ) ? $order->id : $order->get_id(),
        );

        $text_on_statement = WC_QP()->s('quickpay_text_on_statement');
        if (!empty($text_on_statement)) {
            $base_params['text_on_statement'] = $text_on_statement;
        }
        
        $order_params = $order->get_transaction_params();
        
        $params = array_merge( $base_params, $order_params );

    	$payment = $this->post( '/', $params);
        
        return $payment;
    }  
    
    
    /**
	* create_link function.
	* 
	* Creates or updates a payment link via the API
	*
 	* @since  4.5.0 
	* @access public
	* @param  int $transaction_id
	* @param  WC_QuickPay_Order $order
	* @return object
	* @throws QuickPay_API_Exception
	*/   
    public function patch_link($transaction_id, WC_QuickPay_Order $order ) 
    {         
        $cardtypelock = WC_QP()->s( 'quickpay_cardtypelock' );

        $payment_method = strtolower(version_compare( WC_VERSION, '3.0', '<' ) ? $order->payment_method : $order->get_payment_method());

        $base_params = array(
            'language'                      => WC_QP()->get_gateway_language(),
            'currency'                      => WC_QP()->get_gateway_currency( $order ),
            'callbackurl'                   => WC_QuickPay_Helper::get_callback_url(),
            'autocapture'                   => WC_QuickPay_Helper::option_is_enabled( $order->get_autocapture_setting() ),
            'autofee'                       => WC_QuickPay_Helper::option_is_enabled( WC_QP()->s( 'quickpay_autofee' ) ),
            'payment_methods'               => apply_filters('woocommerce_quickpay_cardtypelock_' . $payment_method, $cardtypelock, $payment_method),
            'branding_id'                   => WC_QP()->s( 'quickpay_branding_id' ),
            'google_analytics_tracking_id'  => WC_QP()->s( 'quickpay_google_analytics_tracking_id' ),
            'customer_email' 				=> version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_email : $order->get_billing_email(),
        );
        
        $order_params = $order->get_transaction_link_params();
        
        $merged_params = array_merge( $base_params, $order_params );

		$params = apply_filters( 'woocommerce_quickpay_transaction_link_params', $merged_params, $order, $payment_method );

    	$payment_link = $this->put( sprintf( '%d/link', $transaction_id ), $params);

        return $payment_link;
    }
	
	
	/**
	 * get_cardtype function
	 * 
	 * Returns the payment type / card type used on the transaction
	 * @since  4.5.0
	 * @return mixed
	 * @throws QuickPay_API_Exception
	 */
	public function get_brand() {
		if( ! is_object( $this->resource_data ) )
		{
			throw new QuickPay_API_Exception( 'No API payment resource data available.', 0 );
		}
		return $this->resource_data->metadata->brand;
	}
	
	/**
	 * get_balance function
	 *
	 * Returns the transaction balance
	 * @since  4.5.0
	 * @return mixed
	 * @throws QuickPay_API_Exception
	 */
	public function get_balance() {
		if( ! is_object( $this->resource_data ) )
		{
			throw new QuickPay_API_Exception( 'No API payment resource data available.', 0 );
		}
		return ! empty($this->resource_data->balance) ? $this->resource_data->balance : NULL;
	}
	
	/**
	 * get_formatted_balance function
	 *
	 * Returns a formatted transaction balance
	 * @since  4.5.0
	 * @return mixed
	 * @throws QuickPay_API_Exception
	 */	
	public function get_formatted_balance() {
		return WC_QuickPay_Helper::price_normalize( $this->get_balance() );
	}

	/**
	 * get_currency function
	 *
	 * Returns a transaction currency
	 * @since  4.5.0
	 * @return mixed
	 * @throws QuickPay_API_Exception
	 */
	public function get_currency() {
		if( ! is_object( $this->resource_data ) )
		{
			throw new QuickPay_API_Exception( 'No API payment resource data available.', 0 );
		}
		return $this->resource_data->currency;
	}
	
	/**
	 * get_remaining_balance function
	 *
	 * Returns a remaining balance
	 * @since  4.5.0
	 * @return mixed
	 * @throws QuickPay_API_Exception
	 */	
	public function get_remaining_balance() {
		$balance = $this->get_balance();

		$authorized_operations = array_filter($this->resource_data->operations, function($operation) {
			return 'authorize' === $operation->type;
		});

		if ( empty( $authorized_operations ) ) {
			return;
		}

		$operation = reset($authorized_operations);

		$amount = $operation->amount;

		$remaining = $amount;

		if ($balance > 0) {
			$remaining = $amount - $balance;
		}

		return $remaining;
	}
	
	/**
	 * get_formatted_remaining_balance function
	 *
	 * Returns a formatted transaction balance
	 * @since  4.5.0
	 * @return mixed
	 * @throws QuickPay_API_Exception
	 */
	public function get_formatted_remaining_balance() {
		return WC_QuickPay_Helper::price_normalize( $this->get_remaining_balance() );
	}

	/**
	 * Checks if either a specific operation or the last operation was successful.
	 * @param null $operation
	 * @return bool
	 * @since 4.5.0
	 * @throws QuickPay_API_Exception
	 */
	public function is_operation_approved( $operation = NULL ) {
		if( ! is_object( $this->resource_data ) )
		{
			throw new QuickPay_API_Exception( 'No API payment resource data available.', 0 );
		}

		if( $operation === NULL ) {
			$operation = $this->get_last_operation();
		}

		return $this->resource_data->accepted && $operation->qp_status_code == 20000 && $operation->aq_status_code == 20000;
	}

	/**
	 * get_metadata function
	 *
	 * Returns the metadata of a transaction
	 * @since  4.5.0
	 * @return mixed
	 * @throws QuickPay_API_Exception
	 */
	public function get_metadata() {
		if( ! is_object( $this->resource_data ) )
		{
			throw new QuickPay_API_Exception( 'No API payment resource data available.', 0 );
		}
		return $this->resource_data->metadata;
	}

	/**
	 * get_state function
	 *
	 * Returns the current transaction state
	 * @since  4.5.0
	 * @return mixed
	 * @throws QuickPay_API_Exception
	 */
	public function get_state() {
		if( ! is_object( $this->resource_data ) )
		{
			throw new QuickPay_API_Exception( 'No API payment resource data available.', 0 );
		}
		return $this->resource_data->state;
	}
}