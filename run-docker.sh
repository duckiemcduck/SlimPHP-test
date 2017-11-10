IMG="apiv0"
if [ "[]" == "$(sudo docker image inspect "$IMG":latest)" ]; then
  sudo docker build -t "$IMG" .
fi
sudo docker run -p 8080:80 -d -v "$(pwd)/api":/var/www/html/api/ -v "$(pwd)/apache2Conf/":/etc/apache2/ "$IMG"
 
echo "Servidor docker rodando em: http://localhost:8080"
firefox http:/localhost:8080/api/hello/mundo &
