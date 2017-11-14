<?php
header('Content-Type: application/json');
// abre conexao c/ o postgresql
$connection = openConnection();
session_start();
// intervalo dos meses e dias estão como [0,11] e [1,31]
// ajustando a meta pra nao parecer inatingivel no grafico
$consumo_na_ponta = 0;
$total_sum = 0;
    $mesInicio = "01";
    $year = $_POST["year"];
    $month = $_POST["month"];
    $day = $_POST["day"];
    $query =
           "SELECT 
            sum(energia)/1000 as soma, extract(month from momento) as mes 
            FROM energia_15_min 
            WHERE uid = '000001' AND momento BETWEEN '$year-$mesInicio-$day' AND '$year-12-$day' 
            GROUP BY 2;";
    $sum = pg_fetch_all(pg_query($query));

$response  = array(
                'sum' => $sum,
                'meta' => $_SESSION['meta_diaria']
            );
echo json_encode($response, JSON_NUMERIC_CHECK);
?>
