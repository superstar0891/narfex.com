export default {
  email: /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/,
  name: /^[a-zA-Z -]+$/i,
  login: /^[a-zA-Z0-9\_]+$/i,
  createPassword: {
    uppercase: /[A-Z]/,
    lowercase: /[a-z]/,
    digits: /[0-9]/,
    length: /\S{8,}/,
    specialCharacters: /['/~`!@#$%^&*()_\-+={}[]|;:"<>,.?]/
  }
};
