#!perl -w
use Getopt::Long;
use File::Slurp qw/read_file write_file/;

my $title = '';
my $ismobile = '';
GetOptions('title=s', \$title, 'ismobile=i', \$ismobile);


$\ = '';
opendir DIR, '.';
# Magic trick

map  {
	m/(\d+?)\.(\w{3,4})$/g;
	my $nxt = $1 + 1;
	last  if not defined $1;
	$nxt = 'index'  if ! -e "$nxt.$2";
	write_file((($1 eq '1')? 'index': $1).'.html', template($title, $_, $nxt, $ismobile))  if defined $1;
} grep{m/\.jpg|png|jpeg$/} readdir(DIR);

sub template {
	my ($title, $image, $pageToGo, $ismobile) = @_;

	my $css = '';
	$css = 'width: 360px; margin: 0 auto;'  if $ismobile eq '1';

	return "<html>
		<head>
			<meta charset=\"UTF-8\">
			<title>$title</title>
			<style type=\"text/css\">
				body {
					margin: 0px;
					width: 100%;
					height: 100%;
					$css;
				}
				img { width: 100%; height: auto; }
			</style>
			<script src=\"https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js\"></script>
			<script type=\"text/javascript\">
				jQuery(document).ready(function(\$) {
					\$(\"body\").click(function() {
						document.location = \"$pageToGo.html\";
					});
				});
			</script>
		</head>
		<body>
			<img width=\"auto\" height=\"auto\" src=\"$image\">
		</body>
	</html>";

}


__END__
