import "./LanguageModal.less";

import React, { memo } from "react";
import { connect } from "react-redux";
import SVG from "react-inlinesvg";
import * as utils from "../../../../utils";
import * as UI from "../../../../ui";
import { openModal, translatorSetLangCode } from "src/actions/index";
import { setLang } from "../../../../services/lang";
import { classNames as cn } from "src/utils";

function LanguageModal({
  langList,
  onClose,
  currentLang,
  byTranslator,
  translatorSetLangCode
}) {
  const handleLanguageChange = langCode => {
    if (byTranslator) {
      translatorSetLangCode(langCode);
      return openModal("translator");
    }
    setLang(langCode);
    onClose();
  };

  const getLanguageFlag = langCode => {
    return (
      <SVG src={require(`../../../../asset/site/lang-flags/${langCode}.svg`)} />
    );
  };

  return (
    <div className="LanguageModal">
      <UI.Modal isOpen={true} onClose={onClose}>
        <UI.ModalHeader>{utils.getLang("global_language")}</UI.ModalHeader>
        <div className="LanguageModal__cont">
          <div className="LanguageModal__cont__grid">
            {!!langList &&
              langList.map(lang => (
                <p
                  key={lang.value}
                  className={cn("LanguageModal__cont__lang", {
                    display: lang.display,
                    current: currentLang === lang.value
                  })}
                  onClick={() => handleLanguageChange(lang.value)}
                >
                  {getLanguageFlag(lang.value)}
                  {lang.title}
                </p>
              ))}
          </div>
        </div>
      </UI.Modal>
    </div>
  );
}

export default connect(
  state => ({
    langList: state.default.langList,
    currentLang: state.default.currentLang
  }),
  {
    translatorSetLangCode: translatorSetLangCode
  }
)(memo(LanguageModal));
