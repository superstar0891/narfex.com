import "./PasswordInfo.less";

import React from "react";
import Lang from "../../../../../../components/Lang/Lang";
import REGEXES from "../../../../../constants/regexes";

export default ({ password }) => {
  return (
    <div className="PasswordInfo">
      <Lang name="registration_passwordMustBe" />
      <ul>
        <li
          className={
            REGEXES.createPassword.uppercase.test(password) && "success"
          }
        >
          <Lang name="registration_passwordMustBe_uppercase" />
        </li>
        <li
          className={
            REGEXES.createPassword.lowercase.test(password) && "success"
          }
        >
          <Lang name="registration_passwordMustBe_lowercase" />
        </li>
        <li
          className={REGEXES.createPassword.digits.test(password) && "success"}
        >
          <Lang name="registration_passwordMustBe_digits" />
        </li>
        <li
          className={
            REGEXES.createPassword.specialCharacters.test(password) && "success"
          }
        >
          <Lang name="registration_passwordMustBe_specialCharacters" />
        </li>
        <li
          className={REGEXES.createPassword.length.test(password) && "success"}
        >
          <Lang name="registration_passwordMustBe_length" />
        </li>
      </ul>
    </div>
  );
};
