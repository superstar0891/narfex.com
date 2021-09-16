import "./Footer.less";

import React from "react";
import { connect } from "react-redux";
import { classNames } from "utils";
import * as actions from "src/actions/index";
import COMPANY from "../../../constants/company";
import Lang from "src/components/Lang/Lang";
import * as pages from "../../../constants/pages";

const Footer = props => {
  const handleChangeLanguage = e => {
    e.preventDefault();
    actions.openModal("language");
  };

  const lang = actions.getCurrentLang();

  return (
    <ul className={classNames("CabinetFooter", props.className)}>
      {/*<li className="CabinetFooter__item">*/}
      {/*  <a href={COMPANY.faqUrl} target="_blank" rel="noopener noreferrer">*/}
      {/*    FAQ*/}
      {/*  </a>*/}
      {/*</li>*/}
      <li className="CabinetFooter__item">
        <a
          href={COMPANY.url + pages.FEE}
          target="_blank"
          rel="noopener noreferrer"
        >
          <Lang name="global_fee" />
        </a>
      </li>
      {/*<li className="CabinetFooter__item"><BaseLink router={router} routeName={pages.FAQ}>{utils.getLang("site__footerFAQ")}</BaseLink></li>*/}
      <li className="CabinetFooter__item">
        <span className="link" onClick={handleChangeLanguage}>
          {lang.title}
        </span>
      </li>
    </ul>
  );
};

export default connect(state => ({
  currentLang: state.default.currentLang,
  langList: state.default.langList,
  translator: state.settings.translator
}))(Footer);
