import React from "react";
import Promo from "../MainScreen/components/Promo/Promo";
import Welcome from "../MainScreen/components/Welcome/Welcome";
import About from "./components/About/About";
import Roadmap from "./components/Roadmap/Roadmap";
import Lang from "../../../components/Lang/Lang";
import COMPANY from "../../../index/constants/company";
import * as utils from "../../../utils";
import { Helmet } from "react-helmet";

export default () => {
  return (
    <div>
      <Helmet>
        <title>
          {[
            COMPANY.name,
            utils.getLang("landingCompany_promo_title", true)
          ].join(" - ")}
        </title>
        <meta
          name="description"
          content={utils.getLang("landingCompany_promo_description")}
        />
      </Helmet>
      <Promo
        title={<Lang name="landingCompany_promo_title" />}
        description={<Lang name="landingCompany_promo_description" />}
        actionButtonText={<Lang name="landingCompany_promo_actionButton" />}
        image={require("../MainScreen/components/Promo/assets/company.svg")}
      />
      <About />
      {/*<Roadmap />*/}
      <Welcome />
    </div>
  );
};
