import "./Footer.less";
import React from "react";
import { Logo } from "src/ui";
import COMPANY from "../../../../index/constants/company";
import Socials from "../../../Landing/components/Socials/Socials";

export default props => {
  return (
    <div className="TokenWrapper__footer">
      <div className="TokenWrapper__content Footer__bottom">
        <div className="Footer__logo">
          <Logo type="monochrome" />
        </div>
        <div className="Footer__copyright">
          © {new Date().getYear() + 1900} {COMPANY.name}
        </div>
        <Socials />
      </div>
    </div>
  );
};
