import "./AdminWrapper.less";

import React, { memo, useEffect } from "react";
import { useSelector } from "react-redux";

import { classNames as cn } from "../../utils/index";
import Header from "../../admin/components/Header/Header";
import Menu from "../../admin/components/Menu/Menu";
import ContentBox from "../../ui/components/ContentBox/ContentBox";
import LoadingStatus from "../../index/components/cabinet/LoadingStatus/LoadingStatus";
import { adminPendingSelector } from "../../selectors";
import COMPANY from "../../index/constants/company";
import { Helmet } from "react-helmet";
import { IS_BITCOINOV_NET } from "../../index/constants/cabinet";

export default memo(props => {
  const pending = useSelector(adminPendingSelector);

  useEffect(() => {
    window.scroll(0, 0);
  }, [props.children.props.routerParams]);

  return (
    <div className={cn("Admin_wrapper", { pending })}>
      <Helmet>
        <link
          rel="icon"
          type="image/png"
          sizes="32x32"
          href={IS_BITCOINOV_NET ? "/favicon-bn.png" : "/favicon-32x32.png"}
        />
        <title>{[COMPANY.name, "Control Panel"].join(" - ")}</title>
      </Helmet>
      <div className="Admin_wrapper__header">
        <Header />
      </div>
      <div className="Admin_wrapper__layout">
        <ContentBox className="Admin_wrapper__menu">
          <Menu />
        </ContentBox>
        <div className="Admin_wrapper__content">
          <div className="Admin_wrapper__loader">
            {pending && <LoadingStatus status={"loading"} />}
          </div>
          {props.children}
        </div>
      </div>
    </div>
  );
});
