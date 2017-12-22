IMG="apiv1"
if [ "[]" == "$(sudo docker image inspect "$IMG":latest)" ]; then
  sudo docker build --no-cache -t "$IMG" .
fi
sudo docker run -d --net=host \
--env-file "$(pwd)/variaveisAmbiente/info.env" \
-v "$(pwd)/api":/var/www/html/api/ \
-v "$(pwd)/controller":/var/www/html/controller/ \
-v "$(pwd)/apache2Conf-php7/":/etc/apache2/ "$IMG" \
 
echo "Servidor docker rodando em: http://localhost:8080"
