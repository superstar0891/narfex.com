import "./BannerNrfx.less";
import React from "react";
import { connect } from "react-redux";
import { getLang } from "src/utils";
import { ButtonWrapper, Button } from "src/ui";
import SVG from "react-inlinesvg";
import * as actions from "../../../../actions";
import router from "../../../../router";
import * as pages from "../../../../index/constants/pages";

const BannerNrfx = props => {
  const handleBuy = () => {
    if (props.isLogin) {
      // actions.openModal("nrfx_presale");
    } else {
      actions.openModal("registration");
    }
  };

  return (
    <div className="BannerNrfx">
      <div className="BannerNrfx__content">
        <h3>{getLang("site_bannerNrfx_title")}</h3>
        <div className="BannerNrfx__main">
          <div className="BannerNrfx__main__text">
            <h2>{getLang("site_bannerNrfx_title")}</h2>
            <ButtonWrapper>
              <Button size="extra_large" onClick={handleBuy} rounded>
                {getLang("token_buyToken")}
              </Button>
              <Button
                size="extra_large"
                type="lite"
                onClick={() => {
                  router.navigate(pages.TOKEN);
                }}
              >
                {getLang("site_readMore")}
                <SVG src={require("src/asset/16px/link.svg")} />
              </Button>
            </ButtonWrapper>
          </div>
          <div className="BannerNrfx__main__logo">
            <SVG src={require("src/asset/token/logo_big.svg")} />
          </div>
        </div>
        <ol className="BannerNrfx__footer">
          <li>
            <strong>1</strong>
            <p>{getLang("site_bannerNrfx_item1")}</p>
          </li>
          <li>
            <strong>2</strong>
            <p>{getLang("site_bannerNrfx_item2")}</p>
          </li>
          <li>
            <strong>3</strong>
            <p>{getLang("site_bannerNrfx_item3")}</p>
          </li>
          <li>
            <strong>4</strong>
            <p>{getLang("site_bannerNrfx_item4")}</p>
          </li>
        </ol>
      </div>
    </div>
  );
};

export default connect(state => ({
  isLogin: !!state.default.profile.user,
  currentLang: state.default.currentLang
}))(BannerNrfx);
