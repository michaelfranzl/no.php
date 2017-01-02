<?PHP

/*
 * no.php (c) 2016, 2017 Michael Franzl
 * 
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
 * CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
 * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */


$backend_url = "https://myapp.backend.com:3000/";
$request_uri = $_SERVER['REQUEST_URI'];

$is_ruby_on_rails = false;
if ( $is_ruby_on_rails == true) {
    # You have to understand the Ruby on Rails Asset pipeline to understand this.
    $uri_rel = "subdir/no.php"; # URI to this file relative to public_html
    $request_uri = str_replace( "$uri_rel/assets", "/assets", $request_uri );
}

$url = $backend_url . $request_uri;


function getRequestHeaders() {
    $headers = array();
    foreach($_SERVER as $key => $value) {
        if(preg_match("/^HTTP/", $key)) { # only keep HTTP headers
            if(preg_match("/^HTTP_HOST/", $key) == 0 && # let curl set the actual host/proxy
            preg_match("/^HTTP_ORIGIN/", $key) == 0 &&
            preg_match("/^HTTP_CONTENT_LEN/", $key) == 0 # let curl set the actual content length
            ) {
                $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                array_push($headers, "$key: $value");
            }
        }
    }
    return $headers;
}

$curl = curl_init( $url );
curl_setopt( $curl, CURLOPT_HTTPHEADER, getRequestHeaders() );
curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true ); # follow redirects
curl_setopt( $curl, CURLOPT_HEADER, true ); # include the headers in the output
curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true ); # return output as string

if ( strtolower($_SERVER['REQUEST_METHOD']) == 'post' ) {
    curl_setopt( $curl, CURLOPT_POST, true );
    $raw_post_data = file_get_contents("php://input");
    curl_setopt( $curl, CURLOPT_POSTFIELDS, $raw_post_data );
}
  
$contents = curl_exec( $curl ); # reverse proxy. the actual request to the backend server.
curl_close( $curl ); # curl is done now


list( $header_text, $contents ) = preg_split( '/([\r\n][\r\n])\\1/', $contents, 2 );

$headers_arr = preg_split( '/[\r\n]+/', $header_text ); 
  
// Propagate headers to response.
foreach ( $headers_arr as $header ) {
    if ( preg_match( '/^Location:/i', $header ) ) {
        # rewrite absolute local redirects to relative ones
        $header = str_replace($backend_url, "/", $header);
    }
    header( $header );
}

print $contents; # return the proxied request result to the browser

?>
