import "./Footer.less";

import React from "react";
import SVG from "react-inlinesvg";

import * as pages from "../../../constants/pages";
import * as utils from "../../../../utils/index";
import * as actions from "actions";
import MarkDown from "ui/MarkDown/MarkDown";
import { isIndonesia } from "src/services/locations";
import * as UI from "src/ui";
import COMPANY from "src/index/constants/company";
// import router from "../../../../router";

export default function Footer() {
  return (
    <div className="Footer">
      <div className="Footer__cont">
        <div className="Footer__links_wrap">
          <div className="Footer__links">
            <div className="Footer__links__title">
              {utils.getLang("site__footerProducts")}
            </div>
            <a href={`/${pages.WALLET}`} className="Footer__links__item">
              {utils.getLang("site__homeWallet")}
            </a>
            <a href={`/${pages.SITE_EXCHANGE}`} className="Footer__links__item">
              {utils.getLang("site__footerExchange")}
            </a>
            {/*<a href={`/${pages.ROBOTS}`} className="Footer__links__item">{utils.getLang('site__footerRobots')}</a>*/}
            {/*<a href={`/${pages.INVESTMENT}`} className="Footer__links__item">{utils.getLang('site__footerInvestments')}</a>*/}
            {/*<a href={`/${pages.COMMERCE}`} className="Footer__links__item">{utils.getLang('site__footerPayment')}</a>*/}
          </div>
          <div className="Footer__links">
            <div className="Footer__links__title">
              {utils.getLang("site__footerCompany")}
            </div>
            <a href={`/${pages.ABOUT}`} className="Footer__links__item">
              {utils.getLang("site__footerAboutUs")}
            </a>
            <a href={`/${pages.TOKEN}`} className="Footer__links__item">
              {utils.getLang("global_nrfxToken")}
            </a>
            <a href={`/${pages.FEE}`} className="Footer__links__item">
              {utils.getLang("site__headerFee")}
            </a>
            <a href={`/${pages.TECHNOLOGY}`} className="Footer__links__item">
              {utils.getLang("site__footerTechnology")}
            </a>
            <a href={`/${pages.SAFETY}`} className="Footer__links__item">
              {utils.getLang("site__footerSecurity")}
            </a>
          </div>
          <div className="Footer__links">
            <div className="Footer__links__title">
              {utils.getLang("site__footerHelp")}
            </div>
            <a
              href={COMPANY.faqUrl}
              rel="noopener noreferrer"
              target="_blank"
              className="Footer__links__item"
            >
              {utils.getLang("site__footerFAQ")}
            </a>
            <a href={`/${pages.CONTACT}`} className="Footer__links__item">
              {utils.getLang("site__footerContactUs")}
            </a>
            {/*<span*/}
            {/*  onClick={() => router.navigate(pages.DOCUMENTATION)}*/}
            {/*  className="Footer__links__item"*/}
            {/*>*/}
            {/*  {utils.getLang("site__headerDocumentation")}*/}
            {/*</span>*/}
            <span
              onClick={() =>
                actions.openModal("static_content", { type: "terms" })
              }
              className="Footer__links__item"
            >
              {utils.getLang("site__footerTermsUse")}
            </span>
            <span
              onClick={() =>
                actions.openModal("static_content", { type: "privacy" })
              }
              className="Footer__links__item"
            >
              {utils.getLang("site__footerPrivacyPolicy")}
            </span>
          </div>
          <div className="Footer__links">
            {/*<div className="Footer__links__title">{utils.getLang('site__footerApplication')}</div>*/}
            {/*<a href="#" className="Footer__links__item">{utils.getLang('site__footerAppStore')}</a>*/}
            {/*<a href={COMPANY.apps.android} className="Footer__links__item">{utils.getLang('site__footerGooglePlay')}</a>*/}
          </div>
        </div>
        <div className="Footer__bottom">
          <div className="Footer__logo">
            <UI.Logo type="gray" />
          </div>
          <div className="Footer__copyright">
            © 2017-{new Date().getYear() + 1900} {COMPANY.name}
          </div>
          <div className="Footer__socials">
            {COMPANY.social.facebook && (
              <a
                target="_blank"
                rel="noopener noreferrer"
                href={"https://" + COMPANY.social.facebook}
                className="Footer__social"
              >
                <SVG src={require("../../../../asset/social/facebook.svg")} />
              </a>
            )}
            {COMPANY.social.twitter && (
              <a
                target="_blank"
                rel="noopener noreferrer"
                href={"https://" + COMPANY.social.twitter}
                className="Footer__social"
              >
                <SVG src={require("../../../../asset/social/twitter.svg")} />
              </a>
            )}
            {COMPANY.social.instagram && (
              <a
                target="_blank"
                rel="noopener noreferrer"
                href={"https://" + COMPANY.social.instagram}
                className="Footer__social"
              >
                <SVG src={require("../../../../asset/social/instagram.svg")} />
              </a>
            )}
            {COMPANY.social.telegram && (
              <a
                target="_blank"
                rel="noopener noreferrer"
                href={"https://" + COMPANY.social.telegram}
                className="Footer__social"
              >
                <SVG src={require("../../../../asset/social/telegram.svg")} />
              </a>
            )}
            {COMPANY.social.vk && (
              <a
                target="_blank"
                rel="noopener noreferrer"
                href={"https://" + COMPANY.social.vk}
                className="Footer__social"
              >
                <SVG src={require("../../../../asset/social/vk.svg")} />
              </a>
            )}
          </div>
        </div>
        {isIndonesia() && (
          <div
            className="Footer__notice"
            onClick={() =>
              actions.openModal("static_content", { type: "risk_statement" })
            }
          >
            {utils.getLang(
              "site_footer_notice",
              <MarkDown content={utils.getLang("site_footer_notice", true)} />
            )}
          </div>
        )}
      </div>
    </div>
  );
}
