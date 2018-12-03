<?php
/**
 * Class Yarns_Microsub_Parser
 */
class Yarns_Microsub_Parser {
	
	/**
	 * Final clean up on post content before saving.
	 *
	 * @param string $data
	 *
	 * @return mixed
	 */
	public static function clean_post( $data ) {
		// dedupe name with summary.
		if ( isset( $data['name'] ) ) {
			if ( isset( $data['summary'] ) ) {
				if ( false !== stripos( $data['summary'], $data['name'] ) ) {
					unset( $data['name'] );
				}
			}
		}
		// dedupe name with content['text'].
		if ( isset( $data['name'] ) ) {
			if ( isset( $data['content']['text'] ) ) {
				if ( false !== stripos( $data['content']['text'], $data['name'] ) ) {
					unset( $data['name'] );
				}
			}
		}
		
		// Attempt to set a featured image
		if ( ! isset( $data['featured'] ) ) {
			if ( isset( $data['photo'] ) && is_array( $data['photo'] ) && 1 === count( $data['photo'] ) ) {
				$data['featured'] = $data['photo'];
				unset( $data['photo'] );
			}
		}
		
		// Convert special characters to html entities in content['html']
		/*
		if ( isset( $data['content']['html'] ) ) {
			//$data['content']['html'] = htmlspecialchars( $data['content']['html']);
		}
		*/
		
		
		// Some feeds return multiple author photos, but only one can be shown
		if ( isset( $data['author']['photo'] ) ) {
			if ( is_array( $data['author']['photo'] ) ) {
				$data['author']['photo'] = $data['author']['photo'][0];
			}
		}
		
		
		//debugging
		$ref_types = [ 'like-of', 'repost-of', 'bookmark-of', 'in-reply-to' ];
		// When these types contain an array (name, url, type) it causes together to crash - see https://github.com/cleverdevil/together/issues/80
		// so reduce them to the url for now
		foreach ( $ref_types as $ref_type ) {
			if ( isset( $data[ $ref_type ]['url'] ) ) {
				$data[ $ref_type ] = $data[ $ref_type ]['url'];
			}
		}
		
		// referecnes
		
		if ( isset( $data['in-reply-to']['url'] ) ) {
			
			//$data['in-reply-to'] = $data['in-reply-to']['url'];
			//unset($data['in-reply-to']);
		}
		
		
		$data = encode_array( array_filter( $data ) );
		
		return $data;
	}
	
	
	
	
	/*Search

	action=search
	query = {URI to search}*/
	
	/*HTTP/1.1 200 Ok
Content-type: application/json

{
  "results": [
	{
	  "type": "feed",
	  "url": "https://aaronparecki.com/",
	  "name": "Aaron Parecki",
	  "photo": "https://aaronparecki.com/images/profile.jpg",
	  "description": "Aaron Parecki's home page"
	},
	{
	  "type": "feed",
	  "url": "https://percolator.today/",
	  "name": "Percolator",
	  "photo": "https://percolator.today/images/cover.jpg",
	  "description": "A Microcast by Aaron Parecki",
	  "author": {
		"name": "Aaron Parecki",
		"url": "https://aaronparecki.com/",
		"photo": "https://aaronparecki.com/images/profile.jpg"
	  }
	},
	{ ... }
  ]
}*/
	
	
	/**
	 * Searches a URL for feeds
	 *
	 * @param $query
	 *
	 * @return array|void
	 *
	 */
	public static function search( $query ) {
		// @@todo Check if the content itself is an rss feed and if so just return that.
		// Check if $query is a valid URL, if not try to generate one
		$url = static::validate_url( $query );
		// Search using Parse-This.
		$search = new Parse_This( $url );
		return $search->fetch_feeds();
	}
	
	
	/**
	 * Returns a preview of the feed
	 *
	 * @param $url URL of the feed to be previewed
	 *
	 * @return array|void
	 */
	public static function preview( $url ) {
		
		
		return static::parse_feed( $url, 5 );
		//return Yarns_Microsub_Aggregator::poll_site($url,'_preview');
	}
	
	/**
	 * Parses feed at $url.  Determines whether the feed is h-feed or rss and passes to appropriate
	 * function.
	 *
	 * @param $url
	 * @param int $count Number of posts to be returned
	 *
	 * @return array|void
	 */
	public static function parse_feed( $url, $count = 20 ) {
		if ( ! $url ) {
			return;
		}
		$args = array(
			'alternate' => false,
			'feed'      => true,
		);
		
		$parse = new Parse_This( $url );
		$parse->fetch();
		$parse->parse($args);
		return $parse->get();
		
	}
	
	/**
	 * Parse RSS feed at $url
	 *
	 * @param $content
	 * @param $url
	 *
	 * @return array
	 */
	public static function parse_rss( $content, $url, $count = 20 ) {
		include_once ABSPATH . WPINC . '/feed.php';
		// Get a SimplePie feed object from the specified feed source.
		$feed = fetch_feed( $url );
		
		return Parse_This_RSS::parse( $feed, $url );
	}
	
	/*

	$url -> the url from which to retrieve a feed
	$count -> the number of posts to retrieve

	*/
	/**
	 * Parses h-feed
	 *
	 * @param $content
	 * @param $url
	 * @param int $count
	 *
	 * @return array
	 */
	public static function parse_hfeed( $content, $url, $count = 5 ) {
		error_log("start parsing");
		
		
			
			
			$feed = Parse_This_MF2::parse($content, $url);
		return $feed;
		
		$mf = static::locate_hfeed( $content, $url );
		//If no h-feed was found, return
		if ( ! $mf ) {
			return;
		}
		
		//return $mf;
		
		// Find the key to use
		if ( ! $mf ) {
			return ( [
				'error'             => 'not_found',
				'error_description' => 'No h-feed was found',
			] );
		} else {
			// Most h-feeds use item, but some use children (e.g. tantek.com)
			if ( array_key_exists( 'items', $mf ) ) {
				$mf_key = 'items';
			} elseif ( array_key_exists( 'children', $mf ) ) {
				$mf_key = 'children';
			} else {
				// If the feed has neither items or chidlren, something has gone wrong
				return 'No feed items';
			}
			
			//return $mf_key;
		}
		//error_log("hfeed item key = " . $mf_key );
		
		// Get feed author
		// (For posts with no author, use the feed author instead)
		$feed_author = static::get_feed_author( $content, $url );
		
		//Get permalinks and contnet for each item
		$hfeed_items = array();
		
		foreach ( $mf[ $mf_key ] as $key => $item ) {
			//error_log ("checkpoint 1.".$key);
			if ( $key >= $count ) {
				break;
			} // Only get up to the specific count of items
			if ( "{$item['type'][0]}" == 'h-entry' ||
			     "{$item['type'][0]}" == 'h-event' ) {
				$the_item = Parse_This_MF2::parse_hentry( $item, $mf );
				if ( is_array( $the_item ) ) {
					/* Merge feed author and post author if:
					 *  (1) feed_author was found AND
					 *  (2) there is no post author OR (3) post author has same url as feed author
					 */
					if ( $feed_author ) {
						if ( isset( $the_item['author'] ) ) {
							//convert author to jf2
							$the_item['author'] = mf2_to_jf2( $the_item['author'] );
							// merge with feed author if there is any missing information
							if ( array_key_exists( 'url', $the_item['author'] ) && array_key_exists( 'url', $feed_author ) ) {
								if ( $the_item['author']['url'] == $feed_author['url'] ) {
									$the_item['author'] = array_merge( $feed_author, $the_item['author'] );
								}
							}
						} else {
							// Post author is not set, so replace it with the feed author
							$the_item['author'] = $feed_author;
							
						}
					}
					
					$the_item      = static::clean_post( $the_item );
					$hfeed_items[] = $the_item;
				}
			}
		}
		
		//$result = ['items'=> $hfeed_items];
		return [
			'items'      => $hfeed_items,
			'_feed_type' => 'h-feed',
		];
		
	}
	
	/**
	 * Finds the author of the feed (supplements post author)
	 *
	 * @param $content
	 * @param $url
	 *
	 * @return array
	 */
	private static function get_feed_author( $content, $url ) {
		$mf = Mf2\parse( $content, $url );
		
		if ( ! is_array( $mf ) ) {
			return array();
		}
		
		$count = count( $mf['items'] );
		if ( 0 === $count ) {
			return array();
		}
		foreach ( $mf['items'] as $item ) {
			// Check if the item is an h-card
			
			if ( Parse_This_MF2::is_type( $item, 'h-card' ) ) {
				// if (Parse_This_MF2::is_hcard($item)){ // deprecated
				
				return Parse_This_MF2::parse_hcard( $item, $mf, $url );
			}
			// Check if the item is an h-feed, in which case look for an author property
			if ( in_array( 'h-feed', $item['type'], true ) ) {
				if ( isset( $item['properties'] ) ) {
					if ( isset( $item['properties']['author'] ) ) {
						foreach ( $item['properties']['author'] as $author ) {
							if ( Parse_This_MF2::is_type( $item, 'hcard' ) ) {
								return Parse_This_MF2::parse_hcard( $author, $mf, $url );
							} else {
								
								return mf2_to_jf2( $author );
							}
						}
					}
				}
				//return Parse_This_MF2::parse_hcard( $item, $mf, $url );
			}
		}
	}
	
	
	public static function parse_hfeed_item( $content, $url ) {
		//$mf = Mf2\fetch($url);
		$mf = Mf2\parse( $content, $url );
		foreach ( $mf['items'] as $item ) {
			if ( '{$item["type"][0]}' == 'h-entry' ||
			     '{$item["type"][0]}' == 'h-event' ) {
				$return_item = array();
				//$return_item = $item['properties'];
				$return_item['type'] = $item['type'];
				
				if ( array_key_exists( 'name', $item['properties'] ) ) {
					$return_item['name'] = $item['properties']['name'];
				}
				
				if ( array_key_exists( 'published', $item['properties'] ) ) {
					$return_item['published'] = $item['properties']['published'];
				}
				
				if ( array_key_exists( 'updated', $item['properties'] ) ) {
					$return_item['updated'] = $item['properties']['updated'];
				}
				
				if ( array_key_exists( 'url', $item['properties'] ) ) {
					$return_item['url'] = $item['properties']['url'];
				}
				
				if ( array_key_exists( 'content', $item['properties'] ) ) {
					$return_item['content'] = $item['properties']['content'];
				}
				
				if ( array_key_exists( 'summary', $item['properties'] ) ) {
					$return_item['summary'] = $item['properties']['summary'];
				}
				
				if ( array_key_exists( 'photo', $item['properties'] ) ) {
					$return_item['photo'] = $item['properties']['photo'];
				}
				
				return $return_item;
			}
		}
	}
	
	
	/**
	 * Find the root feed for a page
	 *
	 * @param $content
	 * @param $url
	 *
	 * @return array|void
	 */
	public static function locate_hfeed( $content, $url ) {
		$mf = Mf2\parse( $content, $url );
		
		if ( ! $mf ) {
			// If no microformats could be parsed, there is no h-feed
			return;
		}
		
		foreach ( $mf['items'] as $mf_item ) {
			if ( in_array( 'h-feed', $mf_item['type'] ) ) {
				return $mf_item;
			}
		}
		
		foreach ( $mf['items'] as $mf_item ) {
			if ( array_key_exists( 'children', $mf_item ) ) {
				foreach ( $mf_item['children'] as $child ) {
					//If h-feed has not been found, check for a child-level h-feed
					if ( "{$child['type'][0]}" == 'h-feed' ) {
						//return 2;
						return $child;
					}
				}
			}
		}
		//If no h-feed was found, check for h-entries. If h-entry is found, then consider its parent the h-feed
		foreach ( $mf['items'] as $mf_item ) {
			if ( "{$mf_item['type'][0]}" == 'h-entry' ) {
				//return 3;
				return $mf;
				//$hfeed_path	="items";
			} else {
				if ( array_key_exists( 'children', $mf_item ) ) {
					//If h-entries have not been found, check for a child-level h-entry
					foreach ( $mf_item['children'] as $child ) {
						if ( "{$child['type'][0]}" == 'h-entry' ) {
							//return 4;
							return $mf_item;
						}
					}
				}
			}
		}
		
		return;
	}
	
	/** DEPRECATED */
	public static function find_hfeed_in_page( $url ) {
		$mf2 = Mf2\fetch( $url );
		
		// If there was more than one h-entry on the page, treat the whole page as a feed
		if ( count( $mf2['items'] ) > 1 ) {
			if ( count(
				     array_filter(
					     $mf2['items'],
					     function ( $item ) {
						     return in_array( 'h-entry', $item['type'] );
					     }
				     )
			     ) > 1 ) {
				#Recognized $url as an h-feed because there are more than one object on the page".
				// Return the whole page as an hfeed
				return $mf2;
			}
		}
		
		// If the first item is an h-feed, parse as a feed
		$first = $mf2['items'][0];
		if ( in_array( 'h-feed', $first['type'] ) ) {
			#Parse::debug("mf2:3: Recognized $url as an h-feed because the first item is an h-feed");
			return $first;
		}
		
		// Fallback case, but hopefully we have found something before this point
		foreach ( $mf2['items'] as $item ) {
			// Otherwise check for a recognized h-* object
			if ( in_array( 'h-entry', $item['type'] ) || in_array( 'h-cite', $item['type'] ) || in_array( 'h-feed', $item['type'] ) ) {
				#Parse::debug("mf2:6: $url is falling back to the first h-entry on the page");
				return $item;
				//break;
			} else {
				foreach ( $item['children'] as $child ) {
					if ( in_array( 'h-entry', $child['type'] ) || in_array( 'h-cite', $child['type'] ) || in_array( 'h-feed', $child['type'] ) ) {
						#Parse::debug("mf2:6: $url is falling back to the first h-entry on the page");
						return $child;
						//$hfeed_exists = True;
						break;
					}
				}
			}
		}
	}
	
	
	/*
	* UTILITY FUNCTIONS
	*
	*/
	
	/**
	 * Corrects invalid URLs if possible
	 *
	 * @param $possible_url
	 *
	 * @return string
	 */
	public static function validate_url( $possible_url ) {
		//If it is already a valid URL, return as-is
		if ( static::is_url( $possible_url ) ) {
			return $possible_url;
		}
		
		// If just a word was entered, append .com
		if ( preg_match( '/^[a-z][a-z0-9]+$/', $possible_url ) ) {
			// if just a word was entered, append .com
			$possible_url = $possible_url . '.com';
		}
		
		// If the URL is missing a trailing '/' add it
		//$possible_url = wp_slash($possible_url)
		
		if ( substr( $possible_url, - 1 ) != '/' ) {
			
			$possible_url .= '/';
		}
		
		// If missing a scheme, prepend with 'http://', otherwise return as-is
		return parse_url( $possible_url, PHP_URL_SCHEME ) === null ? 'http://' . $possible_url : $possible_url;
	}
	
	/**
	 * Returns true if $query is a valid URL
	 *
	 * @param $query
	 *
	 * @return bool
	 */
	public static function is_url( $query ) {
		if ( filter_var( $query, FILTER_VALIDATE_URL ) ) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Returns true if $feedtype represents an RSS/Atom feed
	 *
	 * @param $feedtype
	 *
	 * @return bool
	 */
	public static function isRSS( $feedtype ) {
		$rssTypes = array(
			'application/rss+xml',
			'application/atom+xml',
			'application/rdf+xml',
			'application/xml',
			'text/xml',
			'text/xml',
			'text/rss+xml',
			'text/atom+xml'
		);
		if ( in_array( $feedtype, $rssTypes ) ) {
			return true;
		}
	}
	
	
}