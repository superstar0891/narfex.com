import "./PromoCode.less";

import React from "react";
import Clipboard from "src/index/components/cabinet/Clipboard/Clipboard";
import { ContentBox } from "../../../../../../ui";
import { useSelector } from "react-redux";
import { partnersPromoCodeSelector } from "../../../../../../selectors";
import Lang from "../../../../../../components/Lang/Lang";

export default () => {
  const code = useSelector(partnersPromoCodeSelector);

  return (
    <ContentBox className="PartnerPromoCode">
      <h3 className="PartnerPromoCode__header">
        <Lang name="cabinet_partners_YourPartnerPromoCode" />
      </h3>
      <h3 className="PartnerPromoCode__code">
        <Clipboard text={code} />
      </h3>
      <p>
        <Lang name="cabinet_partners_promoCodeDescription" />
      </p>
    </ContentBox>
  );
};
