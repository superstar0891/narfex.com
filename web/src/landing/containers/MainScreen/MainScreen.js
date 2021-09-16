import React from "react";
import Welcome from "./components/Welcome/Welcome";
import Promo from "./components/Promo/Promo";
import Swap from "./components/Swap/Swap";
import Exchange from "./components/Exchange/Exchange";
import Advantages from "./components/Advantages/Advantages";
import Application from "./components/Application/Application";
import Lang from "../../../components/Lang/Lang";
import COMPANY from "../../../index/constants/company";
import * as utils from "../../../utils";
import { Helmet } from "react-helmet";
import ADVANTAGES_ITEMS from "../../constants/advantages";

export default () => {
  return (
    <div>
      <Helmet>
        <title>
          {[COMPANY.name, utils.getLang("landing_promo_title", true)].join(
            " - "
          )}
        </title>
        <meta
          name="description"
          content={utils.getLang("landing_promo_description")}
        />
      </Helmet>
      <Promo
        title={<Lang name="landing_promo_title" />}
        description={<Lang name="landing_promo_description" />}
        actionButtonText={<Lang name="landing_promo_actionButton" />}
        image={require("./components/Promo/assets/promo.svg")}
        // label={<Lang name="landing_promo_nrfx_label" />}
        // labelDescription={<Lang name="landing_promo_nrfx_description" />}
        // labelLink={<Lang name="global_buy" />}
      />
      <Swap />
      <Exchange />
      <Advantages
        accent
        titleLang="landing_advantages_title"
        items={ADVANTAGES_ITEMS}
      />
      <Application />
      <Welcome />
    </div>
  );
};
