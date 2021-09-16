import "./SiteTokenScreen.less";
import React, { useEffect } from "react";
import { connect } from "react-redux";
import * as firebase from "firebase";

import Promo from "./components/Promo/Promo";
import Benefits from "./components/Benefits/Benefits";
import TokenData from "./components/TokenData/TokenData";
// import TokenBurning from "./components/TokenBurning/TokenBurning";
// import Address from "./components/Address/Address";
import RoadMap from "./components/RoadMap/RoadMap";
import JoinUs from "./components/JounUs/JoinUs";
import Usability from "./components/Usability/Usability";
import * as actions from "../../../../actions";
import { getLang } from "src/utils";
import { Helmet } from "react-helmet";
import * as utils from "../../../../utils";

const SiteTokenScreen = props => {
  useEffect(() => {
    firebase.analytics().logEvent("open_site_token_screen");
  });

  const handleBuy = () => {
    if (props.isLogin) {
      // actions.openModal("nrfx_presale");
    } else {
      actions.openModal("registration");
    }
  };

  const roadMap = [
    { title: getLang("token_roadMapStep1"), time: 1585843200000 },
    { title: getLang("token_roadMapStep2"), time: 1586620800000, price: 0.15 },
    { title: getLang("token_roadMapStep3"), time: 1591891200000, price: 0.5 },
    { title: getLang("token_roadMapStep4"), time: 1597190400000, price: 0.8 },
    { title: getLang("token_roadMapStep5"), time: 1599868800000, price: 1.0 }
  ];

  return (
    <div id="Main" className="SiteTokenScreen">
      <Helmet>
        <title>{utils.getLang("global_nrfxToken", true)}</title>
        <meta
          name="description"
          content={utils.getLang("token_promoText", true)}
        />
      </Helmet>
      <Promo roadMap={roadMap} onBuy={handleBuy} />
      <Benefits />
      <TokenData onBuy={handleBuy} />
      <RoadMap items={roadMap} />
      {/*<TokenBurning onBuy={handleBuy} />*/}
      <Usability />
      {/*<Address />*/}
      <JoinUs onBuy={handleBuy} />
    </div>
  );
};

export default connect(state => ({
  isLogin: !!state.default.profile.user
}))(SiteTokenScreen);
