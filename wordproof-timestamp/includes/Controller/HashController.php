<?php

namespace WordProofTimestamp\includes\Controller;

use WordProofTimestamp\includes\DomainHelper;
use WordProofTimestamp\includes\PostHelper;
use WordProofTimestamp\includes\PostMetaHelper;
use WordProofTimestamp\includes\ProductHelper;

class HashController {

	/**
	 * Create the hash for new posts
	 *
	 * @param $post
	 * @param  bool  $raw
	 *
	 * @return bool|object|string
	 */
	public static function getHash( $post ) {
		return hash( 'sha256', self::getPostsJSON($post));
	}
    
    /**
     * Get posts as JSON
     *
     * @param $post
     * @param  bool  $raw
     *
     * @return bool|object|string
     */
    public static function getPostsJSON( $post ) {
        return json_encode( self::getRawPosts($post), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
    }
    
    public static function getRawPosts( $post ) {
        if ( is_int( $post ) ) {
            $post = get_post( $post );
        }
        
        $fields = self::getFields( $post );
        $fields = array_merge( $fields['properties'], $fields['attributes'] );
        
        return $fields;
    }

	public static function getFields( $post ) {
		if ( is_int( $post ) ) {
			$post = get_post( $post );
		}

		$properties = self::getProperties( $post );
		$attributes = self::getAttributes( $post );

		return [ 'properties' => $properties, 'attributes' => $attributes ];
	}

	/**
	 * @param $post
	 *
	 * @return array|null
	 */
	private static function getProperties( $post ) {
		switch ( self::getType( $post ) ) {
			case ARTICLE_TIMESTAMP:
				$array            = [];
				$array['type']    = ARTICLE_TIMESTAMP;
				$array['version'] = CURRENT_TIMESTAMP_STANDARD_VERSION;
				$array['title']   = $post->post_title;
				$array['content'] = PostHelper::getContent( $post );
				$array['date']    = get_the_modified_date( 'c', $post );

				return $array;
			case MEDIA_OBJECT_TIMESTAMP:
				$array                   = [];
				$array['type']           = MEDIA_OBJECT_TIMESTAMP;
				$array['version']        = CURRENT_TIMESTAMP_STANDARD_VERSION;
				$array['title']          = $post->post_title;
				$array['contentHash']    = hash_file( 'sha256', get_attached_file( $post->ID ) );
				$array['contentUrl']     = $post->guid;
				$array['encodingFormat'] = $post->post_mime_type;
				$array['date']           = get_the_modified_date( 'c', $post );

				return $array;
			case PRODUCT_TIMESTAMP:
				$array                = [];
				$array['type']        = PRODUCT_TIMESTAMP;
				$array['version']     = CURRENT_TIMESTAMP_STANDARD_VERSION;
				$array['name']        = $post->post_title;
				$array['description'] = ProductHelper::getDescription( $post );
				$array['date']        = get_the_modified_date( 'c', $post );

				return $array;
			default:
				return null;
		}
	}

	/**
	 * @param $post
	 *
	 * @return array|mixed|void|null
	 */
	public static function getAttributes( $post ) {
		switch ( self::getType( $post ) ) {
			case ARTICLE_TIMESTAMP:
				$array        = []; //TODO: Get selected attributes
				$array['url'] = DomainHelper::getPermalink( $post->ID );
				$array        = apply_filters( 'wordproof_hash_attributes', $array );

				return $array;
			case MEDIA_OBJECT_TIMESTAMP:
				$array = [];

				return $array;
			case PRODUCT_TIMESTAMP:
				$array = [];
				$array = array_merge( $array, ProductHelper::maybeReturnAttribute( 'productId', $post ) );
				$array = array_merge( $array, ProductHelper::maybeReturnAttribute( 'image', $post ) );
				$array = array_merge( $array, ProductHelper::maybeReturnAttribute( 'price', $post ) );
				$array = array_merge( $array, ProductHelper::maybeReturnAttribute( 'url', $post ) );

				return $array;
			default:
				return null;
		}
	}

	public static function getBlockchainLink( $postId ) {
		$meta = PostMetaHelper::getPostMeta( $postId, [ 'blockchain', 'transactionId' ] );

		if ( isset( $meta->blockchain ) && isset( $meta->transactionId ) ) {
			switch ( $meta->blockchain ) {
				case 'telos_main':
				case 'telos':
					return 'https://telos.bloks.io/transaction/' . $meta->transactionId;
				case 'eosJungle':
				case 'eos_jungle':
					return 'https://jungle.bloks.io/transaction/' . $meta->transactionId;
				case 'eth_ropsten':
					return 'https://ropsten.etherscan.io/tx/' . $meta->transactionId;
				case 'eth_main':
					return 'https://etherscan.io/tx/' . $meta->transactionId;
				default:
					return 'https://bloks.io/transaction/' . $meta->transactionId;
			}
		}

		return '';
	}

	public static function getType( $post ) {
		$postType = get_post_type( $post );
		switch ( $postType ) {
			case 'attachment':
				return MEDIA_OBJECT_TIMESTAMP;
			case 'product':
				return PRODUCT_TIMESTAMP;
			case 'page':
			case 'post':
			default:
				return ARTICLE_TIMESTAMP;
		}
	}
}
