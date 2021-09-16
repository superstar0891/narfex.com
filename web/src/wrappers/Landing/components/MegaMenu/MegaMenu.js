import React from "react";
import "./MegaMenu.less";
import { classNames as cn } from "src/utils/index";
import SVG from "react-inlinesvg";
import AppButtons from "../../../../components/AppButtons/AppButtons";
import * as pages from "../../../../index/constants/pages";
import { useRouter } from "react-router5";
import Lang from "../../../../components/Lang/Lang";

export default ({ visible, onClose }) => {
  const router = useRouter();

  return (
    <div className={cn("MegaMenu", "LandingWrapper__block", { visible })}>
      <div className="MegaMenu__content LandingWrapper__content">
        <ul className="MegaMenu__productList">
          <li
          // onClick={() => {
          //   router.navigate(pages.BUY_BITCOIN);
          //   onClose();
          // }}
          >
            <SVG src={require("src/asset/120/findiri.svg")} />
            <div>
              <h4>
                <Lang name="landing_megaMenu_token_title" />
              </h4>
              <p>
                <Lang name="landing_megaMenu_token_description" />
              </p>
            </div>
          </li>
          <li
          // onClick={() => {
          //   router.navigate(pages.SITE_EXCHANGE);
          //   onClose();
          // }}
          >
            <SVG src={require("src/asset/120/currencies.svg")} />
            <div>
              <h4>
                <Lang name="landing_megaMenu_wallet_title" />
              </h4>
              <p>
                <Lang name="landing_megaMenu_wallet_description" />
              </p>
            </div>
          </li>
        </ul>
        <div className="MegaMenu__image" />
        <div className="MegaMenu__description">
          <h3>
            <Lang name="landing_megaMenu_mobileApplication_title" />
          </h3>
          <p>
            <Lang name="landing_megaMenu_mobileApplication_description" />
          </p>
          <AppButtons />
        </div>
      </div>
    </div>
  );
};
