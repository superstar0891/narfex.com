import React from "react";
import Main from "./components/Main/Main";
// import Social from "./components/Social/Social";
import Welcome from "../MainScreen/components/Welcome/Welcome";
import COMPANY from "../../../index/constants/company";
import * as utils from "../../../utils";
import { Helmet } from "react-helmet";

export default () => {
  return (
    <div>
      <Helmet>
        <title>
          {[COMPANY.name, utils.getLang("site__contactSubTitle", true)].join(
            " - "
          )}
        </title>
        <meta
          name="description"
          content={utils.getLang("site__contactDescription")}
        />
      </Helmet>
      <Main />
      {/*<Social accent />*/}
      <Welcome />
    </div>
  );
};
