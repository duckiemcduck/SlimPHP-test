<?php
	header('Content-Type: application/json');
	// abre conexao c/ o postgresql
	$connection = openConnection();
	session_start();
	// intervalo dos meses e dias estão como [0,11] e [1,31]
	// ajustando a meta pra nao parecer inatingivel no grafico
	$consumo_na_ponta = 0;
	$total_sum = 0;
	$uids = [
				"florianopolis" => "000001",
				"lages" => "000002",
				"canoinhas" => "000003",
				"reitoria" => "000004"
			];
		
	$uid = key($uids);
	if(array_key_exists($local, $uids))
	{
		$uid = $uids[$local];
	}
	
    $mesInicio = "01";
    $sql = "SELECT 
            sum(energia)/1000 as sum, extract(month from momento) as mes 
            FROM energia_15_min 
            WHERE uid = $1 AND momento BETWEEN $2::timestamp AND $3::timestamp 
            GROUP BY 2";
    $resultado =  pg_prepare($connection, "selecaoSoma", $sql);
    $resultado = pg_execute($connection, "selecaoSoma", [$uid,$year . '-' . $mesInicio . '-'.$day, $year.'-' . 12 . '-'.$day]);
    $sum = pg_fetch_all($resultado);
	$json  = array(
					'sum' => $sum,
					'meta' => $_SESSION['meta_diaria']
				);
	$newResponse = $response->withJson($json);
?>
