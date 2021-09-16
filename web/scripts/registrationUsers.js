const fetch = require('node-fetch');
var faker = require('faker');


const apiUrl = "https://stageapi.bitcoinbot.pro/api/v1/";

const registration = () => fetch(apiUrl + "profile/sign_up", {
  headers: {
    "content-type":"application/json",
  },
  body: JSON.stringify({
    email: faker.internet.email(),
    refer: "borodindk",
    invite_link: null
  }),
  method: "PUT"
}).then(res => res.json()).then(({hash}) => {
  return fetch(apiUrl + "profile/fill_account", {
    headers: {
      "content-type":"application/json",
    },
    body: JSON.stringify({
      first_name: faker.name.firstName(),
      last_name: faker.name.lastName(),
      login: faker.internet.userName(),
      password: "qwerty",
      hash: hash
    }),
    method: "PUT"
  }).then(res => res.json()).then(console.log)
});


for (let i = 0; i < 100; i ++ ) {
  registration();
}
