import React from "react";
import { useSelector } from "react-redux";
import "./Footer.less";
import { Logo } from "../../../../ui";
import SVG from "react-inlinesvg";
import AppButtons from "../../../../components/AppButtons/AppButtons";
import Copyright from "../Copyright/Copyright";
import { Select } from "src/ui/index";
import { getCssVar } from "../../../../utils";
import { customStyles } from "../../../../ui/components/Select/Select";
import { currentLangSelector, langListSelector } from "../../../../selectors";
import { setLang } from "../../../../services/lang";
import { Link } from "react-router5";
import * as pages from "../../../../index/constants/pages";
import * as actions from "../../../../actions";
import COMPANY from "../../../../index/constants/company";
import Lang from "../../../../components/Lang/Lang";
import Socials from "../Socials/Socials";

const getLanguageFlag = langCode => {
  return (
    <div className="Footer__lang__flag">
      <SVG src={require(`../../../../asset/site/lang-flags/${langCode}.svg`)} />
    </div>
  );
};

export default () => {
  const langList = useSelector(langListSelector);
  const currentLang = useSelector(currentLangSelector);

  return (
    <div className="Footer LandingWrapper__block">
      <div className="Footer__content LandingWrapper__content">
        <div className="Footer__main">
          <Logo className="Footer__logo" />
          <Select
            className="Footer__lang"
            styles={{
              control: (provided, state) => ({
                ...customStyles.control(provided, state),
                boxShadow: null,
                border: `1px solid ${getCssVar("--cloudy")}`,
                borderRadius: 16,
                minHeight: 40
              }),
              option: (provided, state) => ({
                ...customStyles.option(provided, state),
                padding: "11px 16px"
              })
            }}
            onChange={o => setLang(o.value)}
            value={currentLang}
            options={langList
              .filter(l => l.display)
              .map(l => ({
                ...l,
                label: l.title,
                icon: getLanguageFlag(l.value)
              }))}
          />
          {/*<Socials />*/}
          <div className="desktopBlock">
            <AppButtons className="Footer__appButtons" />
            <Copyright className="Footer__copyright" />
          </div>
        </div>
        <nav className="Footer__nav">
          {/*<ul>*/}
          {/*  <li>*/}
          {/*    <h4>*/}
          {/*      <Lang name="landing_footer_products" />*/}
          {/*    </h4>*/}
          {/*  </li>*/}
          {/*  <li>*/}
          {/*    <Link routeName={pages.BUY_BITCOIN}>*/}
          {/*      <Lang name="landing_footer_buyBitcoin" />*/}
          {/*    </Link>*/}
          {/*  </li>*/}
          {/*  /!*<li>*!/*/}
          {/*  /!*  <span><Lang name="landing_footer_buyEthereum" /></span>*!/*/}
          {/*  /!*</li>*!/*/}
          {/*  /!*<li>*!/*/}
          {/*  /!*  <span>*!/*/}
          {/*  /!*    <Lang name="landing_footer_swap" />*!/*/}
          {/*  /!*  </span>*!/*/}
          {/*  /!*</li>*!/*/}
          {/*  <li>*/}
          {/*    <Link routeName={pages.SITE_EXCHANGE}>*/}
          {/*      <Lang name="landing_footer_exchange" />*/}
          {/*    </Link>*/}
          {/*  </li>*/}
          {/*</ul>*/}
          <ul>
            <li>
              <h4>
                <Lang name="landing_footer_company" />
              </h4>
            </li>
            <li>
              <Link routeName={pages.ABOUT}>
                <Lang name="landing_footer_about" />
              </Link>
            </li>
            <li>
              <Link routeName={pages.FEE}>
                <Lang name="landing_footer_fee" />
              </Link>
            </li>
            {/*<li>*/}
            {/*  <Link routeName={pages.TOKEN}>Findiri Token</Link>*/}
            {/*</li>*/}
            {/*<li>*/}
            {/*  <Link routeName={pages.SAFETY}>*/}
            {/*    <Lang name="landing_footer_security" />*/}
            {/*  </Link>*/}
            {/*</li>*/}
          </ul>
          <ul>
            <li>
              <h4>
                <Lang name="landing_footer_help" />
              </h4>
            </li>
            {/*<li>*/}
            {/*  <a*/}
            {/*    href={COMPANY.faqUrl}*/}
            {/*    rel="noopener noreferrer"*/}
            {/*    target="_blank"*/}
            {/*  >*/}
            {/*    <Lang name="landing_footer_faq" />*/}
            {/*  </a>*/}
            {/*</li>*/}
            <li>
              <Link routeName={pages.CONTACT}>
                <Lang name="landing_footer_support" />
              </Link>
            </li>
            <li>
              <span
                onClick={() =>
                  actions.openModal("static_content", { type: "privacy" })
                }
              >
                <Lang name="landing_footer_privacy" />
              </span>
            </li>
            <li>
              <span
                onClick={() =>
                  actions.openModal("static_content", { type: "terms" })
                }
              >
                <Lang name="landing_footer_terms" />
              </span>
            </li>
          </ul>
        </nav>
        <div className="mobileBlock">
          <AppButtons className="Footer__appButtons" />
          <Copyright className="Footer__copyright" />
        </div>
      </div>
    </div>
  );
};
