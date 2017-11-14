<?php
function openConnection($database_name = 'smartifsc', $exit = TRUE) {
    $connection = pg_connect("host=labsmart.florianopolis.ifsc.edu.br port=5432 dbname=$database_name user=smartifsc_website password=51KTrPq3205 connect_timeout=1");
    if ($connection) {
        return $connection;
    }
    else {
        if ($exit) {
            echo json_encode(null);
            // finaliza o script que chamou essa função, sem ler as próximas linhas.
            exit();
        }
        else {
            return $connection;
        }
    }
}
function closeConnection($connection) {
    pg_close($connection);
}
// recebe três colunas do banco de dados: erros, momentos (EM EPOCH) e a do dado de interesse. O parâmetro $minute é o intervalo das medias.
// a motivação dessa função é não aparecer nos gráficos dados com valores não confiáveis (com erros), assim os substitui por valores nulls (gaps no gráfico)
function setErroValuesToNull($erro_s, $momento_s, $data_s, $minutes) {
    $seconds = $minutes*60;
    $erros_indexes = array_keys($erro_s, 't');
    $index_offset = 0;
    // verifica o primeiro valor dos $momento_s, para evitar offset quando server voltar a receber os dados apenas em um dia distinto.
    if (!empty($momento_s[0])) {
        // como nessa função estou trabalhando com diferença dos timestamps, devo usar timezone = UTC para criar DateTimes.
        $aux = new DateTime( "@{$momento_s[0]}", new DateTimeZone('UTC') );
        $midnight = new DateTime( $aux->format('Y-m-d 00:00:00'), new DateTimeZone('UTC') );
        $midnight_timestamp = $midnight->format('U');
        $diff = $momento_s[0] - $midnight_timestamp; // how many seconds since midnight
        $qntd_nulls = floor($diff/$seconds); // round to the nearest low integer
        //error_log("aux: {$aux->format('Y-m-d')}\nmidnight_timestamp: $midnight_timestamp\n momento_s[0]: {$momento_s[0]}\n qntd_nulls: $qntd_nulls\n diff: $diff\n", 3, '/home/rgnagel/testes.log');
        $nulls = array();
        while (sizeof($nulls) != $qntd_nulls) {
            $nulls[] = NULL;
        }
        // não remove nenhum elemento do array, apenas adiciona NULLs no começo.
        array_splice($data_s, 0, 0, $nulls);
        $index_offset += $qntd_nulls;
    }
    foreach ($erros_indexes as $i) {
        // só percorre indexes com erro
        if ($i != 0) {
            $diff = (int)$momento_s[$i] - (int)$momento_s[$i - 1];
            if ($diff > $seconds) {
                $data_s[$i + $index_offset] = NULL;
                $qntd_nulls = floor($diff/$seconds) - 1;
                $nulls = array();
                while (sizeof($nulls) != $qntd_nulls) {
                    $nulls[] = NULL;
                }
                array_splice($data_s, $i + $index_offset, 0, $nulls);
                $index_offset += $qntd_nulls;
            }
        }
    }
    return $data_s;
}

// conexao que precisa estar aberta para esta função funcionar: openConnection('clima');
// retorna array com índice númerico cujos valores são temperatura máxima e mínima, respectivamente.
function temperaturasDoDia($local, $yyyy, $mm, $dd) {
    $result = pg_query(
        "SELECT MAX(temp_celsius), MIN(temp_celsius), MAX(uv)
      FROM clima
      WHERE local = '$local' AND momento BETWEEN '$yyyy-$mm-$dd 00:00:00'::timestamp AND '$yyyy-$mm-$dd 23:59:59'::timestamp
    ");
    return pg_fetch_array($result);
}
function todasTemperaturasDoDia($local, $yyyy, $mm, $dd) {
    $result = pg_query(
        "SELECT temp_celsius
      FROM clima
      WHERE local = '$local' AND momento BETWEEN '$yyyy-$mm-$dd 00:00:00'::timestamp AND '$yyyy-$mm-{$dd} 00:00:00'::timestamp + (interval '1 day') ORDER BY momento ASC
    ");
    return pg_fetch_all_columns($result);
}
function UVsDoDia($local, $yyyy, $mm, $dd) {
    $result = pg_query(
        "SELECT uv
      FROM clima
      WHERE local = '$local' AND momento BETWEEN '$yyyy-$mm-$dd 00:00:00'::timestamp AND '$yyyy-$mm-$dd 23:59:59'::timestamp
      ORDER BY momento ASC;
    ");
    return pg_fetch_all_columns($result);
}
function lastUVDoDia($local, $yyyy, $mm, $dd) {
    $result = pg_query(
        "SELECT uv
      FROM clima
      WHERE local = '$local' AND momento BETWEEN '$yyyy-$mm-$dd 00:00:00'::timestamp AND '$yyyy-$mm-$dd 23:59:59'::timestamp
      ORDER BY momento DESC LIMIT 1;
    ");
    return pg_fetch_array($result);
}
// conexao que precisa estar aberta para esta função funcionar: openConnection('clima');
// retorna array com índice númerico cujos valores são temperatura máxima e mínima, respectivamente.
function temperaturasDoMes($local, $yyyy, $mm) {
    $result = pg_query(
        "SELECT MAX(temp_celsius), MIN(temp_celsius)
      FROM clima
      WHERE local = '$local' AND momento BETWEEN '$yyyy-$mm-1 00:00:00'::timestamp AND '$yyyy-$mm-31 23:59:59'::timestamp
    ");
    return pg_fetch_array($result);
}

// consumo de energia é em kWh
// conexao que precisa estar aberta para esta função funcionar: openConnection('smartifsc');
function consumoDeEnergiaDoDia($id_interno, $yyyy, $mm, $dd) {
    $result = pg_query(
        "SELECT sum(energia)/1000
      FROM energia_15_min
      WHERE momento BETWEEN '$yyyy-$mm-$dd 00:00:00' AND '$yyyy-$mm-$dd 23:59:59' AND uid = '$id_interno'
    ");
    return pg_fetch_result($result, 0, 0);
}

// consumo de energia é em kWh
// conexao que precisa estar aberta para esta função funcionar: openConnection('smartifsc');
function consumoDeEnergiaDoMes($id_interno, $yyyy, $mm) {
    $result = pg_query(
      "SELECT sum(energia)/1000
      FROM energia_15_min
      WHERE extract(year from momento) = $yyyy AND extract(month from momento) = $mm AND uid = '$id_interno'
    ");
    return pg_fetch_result($result, 0, 0);
}
function consumoDeEnergiaDaFatura($id_interno, $range_of_days_and_months) {
    echo "to be implemented.";
}

// conexao que precisa estar aberta para esta função funcionar: openConnection('smartifsc');
// retorna um array de índice numérico: carga máxima na ponta e string com o momento formatado, respectivamente.
function cargaMaxNaPontaDia($id_interno, $yyyy, $mm, $dd) {
    $result = pg_query(
        "WITH range as
      (
        SELECT p3, momento
        FROM elo_dados
        WHERE uid = '$id_interno' AND
        momento BETWEEN '$yyyy-$mm-$dd 17:30:00'::timestamp AND '$yyyy-$mm-$dd 20:30:00'::timestamp
      )
      SELECT p3, to_char(momento + (interval '1s'), 'HH24:MI')
      FROM range
      WHERE p3 = (SELECT max(p3) FROM range)"
    );
    return pg_fetch_array($result);
}

// conexao que precisa estar aberta para esta função funcionar: openConnection('smartifsc');
// retorna um array de índice numérico: carga máxima fora da ponta e string com o momento formatado, respectivamente.
function cargaMaxForaDaPontaDia($id_interno, $yyyy, $mm, $dd) {
    $result = pg_query(
        "WITH range as
      (
        SELECT p3, momento
        FROM elo_dados
        WHERE uid = '$id_interno' AND
        momento BETWEEN '$yyyy-$mm-$dd 00:00:00'::timestamp AND '$yyyy-$mm-$dd 23:59:59'::timestamp
        AND
        momento NOT BETWEEN '$yyyy-$mm-$dd 17:30:00'::timestamp AND '$yyyy-$mm-$dd 20:30:00'::timestamp
      )
      SELECT p3, to_char(momento + (interval '1s'), 'HH24:MI')
      FROM range
      WHERE p3 = (SELECT max(p3) FROM range)"
    );
    return pg_fetch_array($result);
}

?>
