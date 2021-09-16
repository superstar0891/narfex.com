import "./Token.less";

import React from "react";
import Welcome from "../MainScreen/components/Welcome/Welcome";
import Promo from "../MainScreen/components/Promo/Promo";
import Lang from "../../../components/Lang/Lang";
import COMPANY from "../../../index/constants/company";
import * as utils from "../../../utils";
import { Helmet } from "react-helmet";
import Functions from "./components/Functions/Functions";
import Saving from "./components/Saving/Saving";
import StartEarning from "./components/StartEarning/StartEarning";
import CashBack from "./components/CashBack/CashBack";
import Bounty from "./components/Bounty/Bounty";
import Roadmap from "./components/Roadmap/Roadmap";
import Release from "./components/Relese/Release";
import FixPrice from "./components/FixPrice/FixPrice";
import Allocation from "./components/Allocation/Allocation";
import Data from "./components/Data/Data";
import Table from "./components/Table/Table";
import { Button, ButtonWrapper } from "../../../ui";
import { buyToken } from "../../../actions/landing/buttons";

export default () => {
  return (
    <div className="LandingToken">
      <Helmet>
        <title>
          {[COMPANY.name, utils.getLang("landingToken_promo_title", true)].join(
            " - "
          )}
        </title>
        <meta
          name="description"
          content={utils.getLang("landingToken_promo_description")}
        />
      </Helmet>
      <Promo
        title={<Lang name="landingToken_promo_title" />}
        description={<Lang name="landingToken_promo_description" />}
        actionButtonText={<Lang name="landingToken_promo_buyToken" />}
        onClick={buyToken}
        image={require("../MainScreen/components/Promo/assets/token.svg")}
      />
      <Functions />
      <Saving />
      <StartEarning />
      <Table />
      <CashBack />
      <Bounty />
      <Roadmap />
      <Release />
      <FixPrice />
      <Data />
      <Allocation />
      <Welcome>
        <q>
          <Lang name="landingToken_end_quote" />
        </q>
        <p className="author">
          <Lang name="landingToken_end_quote_author" />
        </p>

        <h2>
          <Lang name="landingToken_end_title" />
        </h2>
        <ButtonWrapper align="center">
          <Button type="secondary" onClick={buyToken} size="extra_large">
            <Lang name="global_buyToken" />
          </Button>
        </ButtonWrapper>
      </Welcome>
    </div>
  );
};
