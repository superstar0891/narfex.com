import React from "react";
import "./MobileMenu.less";
import { classNames as cn } from "../../../../utils";
import { Button } from "../../../../ui";
import SVG from "react-inlinesvg";
import { useRouter } from "react-router5";
import * as actions from "../../../../actions";
import * as pages from "../../../../index/constants/pages";
import Lang from "../../../../components/Lang/Lang";
import { useSelector } from "react-redux";
import { userSelector } from "../../../../selectors";

export default ({ visible, onClose }) => {
  const router = useRouter();
  const user = useSelector(userSelector);

  return (
    <div className={cn("MobileMenu", { visible })}>
      <div className="MobileMenu__buttons">
        <Button
          type="secondary"
          onClick={() => {
            router.navigate(pages.ABOUT);
            onClose();
          }}
        >
          <Lang name="site__headerCompany" />
        </Button>
        <Button
          type="secondary"
          onClick={() => {
            router.navigate(pages.FEE);
            onClose();
          }}
        >
          <Lang name="global_fee" />
        </Button>
        <Button
          type="secondary"
          onClick={() => {
            router.navigate(pages.BUY_BITCOIN);
            onClose();
          }}
        >
          <Lang name="site__headerContactUs" />
        </Button>
        <Button
          type="secondary"
          onClick={() => {
            router.navigate(pages.TOKEN);
          }}
        >
          Findiri Token
        </Button>
      </div>
      <ul className="MobileMenu__productList">
        <li
          onClick={() => {
            router.navigate(pages.BUY_BITCOIN);
          }}
        >
          <SVG src={require("src/asset/120/currencies.svg")} />
          <div>
            <h4>
              <Lang name="landing_megaMenu_buyCrypto_title" />
            </h4>
            <p>
              <Lang name="landing_megaMenu_buyCrypto_description" />
            </p>
          </div>
        </li>
        <li
          onClick={() => {
            router.navigate(pages.SITE_EXCHANGE);
          }}
        >
          <SVG src={require("src/asset/120/swap.svg")} />
          <div>
            <h4>
              <Lang name="landing_megaMenu_trade_title" />
            </h4>
            <p>
              <Lang name="landing_megaMenu_trade_description" />
            </p>
          </div>
        </li>
      </ul>
      <div className="MobileMenu__authButtons">
        {user ? (
          <Button
            onClick={() => {
              router.navigate(pages.WALLET);
            }}
          >
            <Lang name="cabinet_header_cabinet" />
          </Button>
        ) : (
          <>
            <Button
              onClick={() => {
                actions.openModal("registration");
              }}
            >
              <Lang name="site__authModalSignUpBtn" />
            </Button>
            <Button
              onClick={() => {
                actions.openModal("login");
              }}
              type="secondary"
            >
              <Lang name="site__authModalLogInBtn" />
            </Button>
          </>
        )}
      </div>
    </div>
  );
};
