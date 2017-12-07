<?php

include_once 'php-zip/src/Zip.php';

$structure = array(
	'excluded_filenames' => array('..', '.'),
	'template_creator' => 'simula_template.pl',
	'base_link' => "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]",
	'htaccess_filename' => '_htaccess',
	'is_mobile' => (isset($_REQUEST['is_mobile']) && !empty($_REQUEST['is_mobile']))? '1': '0',
);

if ( ( (isset($_REQUEST['pathname']) && !empty($_REQUEST['pathname']))
	||
	(isset($_REQUEST['new_pathname']) && !empty($_REQUEST['new_pathname']))
	) && (isset($_FILES['zipfile']) && !empty($_FILES['zipfile'])) ) {

	$pathname = !empty($_REQUEST['pathname'])? $_REQUEST['pathname']: $_REQUEST['new_pathname'];
	chdir('pages');

	if (is_dir($pathname)) {
		chdir($pathname);

	} else {
		mkdir($pathname);
		chdir($pathname);
	}

	$zipFilename = $_FILES['zipfile']['name'];

	copy("../../{$structure['template_creator']}", $structure['template_creator']);
	copy($_FILES['zipfile']['tmp_name'], $zipFilename);
	copy("../../{$structure['htaccess_filename']}", '.htaccess');


	$zip = new Zip();
	$zip->unzip_file($zipFilename);
	$zip->unzip_to('.');

	system("perl {$structure['template_creator']} --title=$pathname --ismobile={$structure['is_mobile']}");
	unlink($structure['template_creator']);
	unlink($zipFilename);
	system("chmod -R 777 ../*");
	exit(true);

} else if (isset($_REQUEST['path']) && !empty($_REQUEST['path'])) {
	chdir('pages');
	$path = $_REQUEST['path'];

	$files = glob($path.'*', GLOB_MARK);
	foreach ($files as $file)
		if (!is_dir($file))  unlink($file);
	if ($path != '/') system("rm -rf $path");
	exit(true);

}

$paths_list = array();
$dirHandle = opendir("pages");
while (false !== ($filename = readdir($dirHandle))) {
	if (!in_array($filename, $structure['excluded_filenames']))
		array_push($paths_list, $filename);
}

?>
<html>
	<head>
		<title>FrogSimula</title>
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css">

		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>
		<script src="https://cdn.jsdelivr.net/npm/clipboard@1/dist/clipboard.min.js"></script>
		<style type="text/css"> .modal {display:    none; position:   fixed; z-index:    1000; top:        0; left:       0; height:     100%; width:      100%; background: rgba( 255, 255, 255, .8 ) url('http://i.stack.imgur.com/FhHRx.gif') 50% 50% no-repeat; } /* enquanto estiver carregando, o scroll da página estará desativado */ body.loading {overflow: hidden; } /* a partir do momento em que o body estiver com a classe loading,  o modal aparecerá */ body.loading .modal {display: block; } </style> </head>
	<body>
		<div class="modal"></div>
		<div class="container" style="padding: 50px;">
			<div class="row">
				<div class="col-md-6">
					<form id="mainForm" name="mainForm" enctype="multipart/form-data" method="POST" onsubmit="return false;">
						<div class="form-group">
							<label for="zipfile">Coloca o zip com as imagens aqui</label>
							<input id="zipfile" type="file" name="zipfile" class="form-control"/>
						</div>
						<div class="form-group">
							<label for="is_mobile">Vai ser mobile?
							<input id="is_mobile" type="checkbox" name="is_mobile" class="form-control" style="box-shadow: none;"></label>
						</div>
						<div class="form-group">
							<label for="pathname">Escreve aqui se for um link novo</label>
							<input id="pathname" type="text" name="new_pathname" class="form-control">
						</div>
						<div class="form-group">
							<label for="spathname">Ou escolhe aqui se for para atualizar</label>
							<select name="pathname" id="spathname" class="form-control">
								<option value="">Selecione uma pasta existente</option>
								<?php foreach($paths_list as $path): ?>
									<option value="<?= $path; ?>"><?= $path; ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<input id="createLink" type="button" value="Criar" class="form-control btn-success">
					</form>
				</div>
				<div class="col-md-6">
					<h3>Lista de Links Criados</h3>
					<ul class="list-group">
						<?php foreach($paths_list as $path): ?>
							<li class="list-group-item">
								<button class="clip" data-clipboard-target="#copy_<?= $path ?>" title="Clique para Copiar o Link">
									<i class="glyphicon glyphicon-paperclip"></i>
								</button>
								<a id="copy_<?= $path ?>" href="<?= "{$structure['base_link']}pages/$path" ?>" target="__blank"><?= "{$structure['base_link']}pages/$path" ?></a>
								<a data-path="<?= $path; ?>" class="pull-right deltePath" href="javascript:;" title="adivinha?">X</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		</div>
	</body>
</html>
<script type="text/javascript">
	jQuery(document).ready(function($) {

		$(".deltePath").click(function() {
			var $this = $(this),
				path = $this.data('path');

				$body.addClass("loading");
				$.ajax({
					type: 'POST',
					data: {
						path: path,
					},
					success: function onSuccess() {
						$body.removeClass("loading");
						swal({
							title: 'Ok',
							text: 'Link deletado',
							type: 'success',
						}, function() {
							window.location.reload();
						});
					},
					error: function onError() {
						swal('Erro', 'Alguma coisa deu errado, não tente novamente.', 'error');
					}
				})
		});

		$body = $("body");
		$(document).on({
			ajaxStart: function() { $body.addClass("loading");    },
			ajaxStop: function() { $body.removeClass("loading"); }
		});

		$("#createLink").click(function() {
			$body.addClass("loading");

			var data =  new FormData($("#mainForm")[0]);
			$.ajax({
				type: 'POST',
				dataType: 'json',
				crossDomain: true,
				data: data,
				cache: false,
				contentType: false,
				processData: false,
				success: function onSuccess() {
					$body.removeClass("loading");
					swal({
						title: 'Feito!',
						text: 'Link criado!',
						type: 'success',
					}, function (){
						window.location.reload();
					});

				},
				error: function onError() {
					swal('Erro!', 'Alguma coisa deu errado, não tente novamente.', 'error');
				}
			});

		});

		$("#pathname").keydown(function() {
			$("#spathname").val($("#spathname option:first").val());
		});

		$("#spathname").change(function() {
			$("#pathname").val("");
		})

		new Clipboard(".clip");
	});
</script>