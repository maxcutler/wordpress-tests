<?php

function rand_str( $len=32 ) {
	return substr( md5( uniqid( rand() ) ), 0, $len );
}