import React from "react";
import "./Main.less";
import SVG from "react-inlinesvg";
import Lang from "../../../../../components/Lang/Lang";
import COMPANY from "../../../../../index/constants/company";
import router from "../../../../../router";
import * as PAGES from "../../../../../index/constants/pages";

export default () => {
  return (
    <div className="LandingWrapper__block Contacts__Main">
      <div className="LandingWrapper__content Contacts__Main__content">
        <h1>
          <Lang name="site__contactSubTitle" />
        </h1>
        <p>
          <Lang name="site__contactDescription" />
        </p>
        <ul>
          <li
            onClick={() => {
              window.jivo_api && window.jivo_api.open();
            }}
          >
            <SVG src={require("../../assets/messages.svg")} />
            <h4>
              <Lang name="site__contactChatTitle" />
            </h4>
            <p>
              <Lang name="site__contactChatDescription" />
            </p>
          </li>
          <li>
            <SVG src={require("../../assets/email.svg")} />
            <h4>
              <Lang name="site__contactEmailTitle" />
              <br />
              <a href={`mailto:${COMPANY.email.support}`}>
                {COMPANY.email.support}
              </a>
            </h4>
            <p>
              <Lang name="site__contactEmailDescription" />
            </p>
          </li>
          <li onClick={() => router.navigate(PAGES.DOCUMENTATION)}>
            <SVG src={require("../../assets/code.svg")} />
            <h4>
              <Lang name="site__contactApiTitle" />
            </h4>
            <p>
              <Lang name="site__contactApiDescription" />
            </p>
          </li>
          <li className="disabled" onClick={() => window.open(COMPANY.faqUrl)}>
            <SVG src={require("../../assets/info.svg")} />
            <h4>
              <Lang name="site__contactFaqTitle" />
            </h4>
            <p>
              <Lang name="site__contactFaqDescription" />
            </p>
          </li>
        </ul>
      </div>
    </div>
  );
};
