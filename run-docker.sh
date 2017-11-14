IMG="apiv0"
if [ "[]" == "$(sudo docker image inspect "$IMG":latest)" ]; then
  sudo docker build -t "$IMG" .
fi
sudo docker run --net=host \
--env-file "$(pwd)/variaveisAmbiente/info.env" \
-v "$(pwd)/api":/var/www/html/api/ \
-v "$(pwd)/controller":/var/www/html/controller/ \
-v "$(pwd)/apache2Conf/":/etc/apache2/ "$IMG" 
 
echo "Servidor docker rodando em: http://localhost:8080"
firefox http:/localhost:8080/api/hello/mundo &
