get_schema:
	- rm ./src/services/apiSchema.js
	- echo "// eslint-disable-next-line" > ./src/services/apiSchema.js
	- echo "export default" > ./src/services/apiSchema.js
	- curl -s https://api.findiri.com/api/v1/documentation/schema >> ./src/services/apiSchema.js

deploy:
	cross-env NODE_ENV=production npm run build
	gcloud app deploy
