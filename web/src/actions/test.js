// styles
// external
// internal
import apiSchema from "../services/apiSchema";
import * as actionTypes from "./actionTypes";
import * as api from "../services/api";

export function update() {
  return dispatch => {
    const AppId = 8;
    const publicKey = "1a4b26bc31-a91649-b63396-253abb8d69";

    api
      .call(apiSchema.Profile.SignInPost, {
        Login: "login",
        Password: "password",
        api_id: AppId,
        public_key: publicKey
      })
      .then(resp => {
        console.log("success", resp);
      })
      .catch(error => {
        dispatch({ type: actionTypes.TEST, message: error.message });
      });
  };
}
