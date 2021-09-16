import React from "react";
import { connect } from "react-redux";
import * as UI from "src/ui";
import SVG from "react-inlinesvg";
import { loadLang, saveTranslator, openStateModal } from "src/actions/index";
import { getLang } from "src/utils";

import "./TranslatorModal.less";
import LoadingStatus from "../LoadingStatus/LoadingStatus";
import Clipboard from "../Clipboard/Clipboard";

class TranslatorModal extends React.Component {
  state = {
    status: "loading",
    saveStatus: "",
    currentValue: "",
    value: ""
  };

  componentDidMount() {
    const { translator, langKey } = this.props;

    if (!translator || !langKey) {
      this.props.onClose();
    }

    this.__load();
  }

  __load() {
    Promise.all(
      ["en", this.props.translatorLangCode].map(code => loadLang(code, false))
    ).then(() => {
      const currentValue = getLang(
        this.props.langKey,
        true,
        this.props.translatorLangCode
      );
      this.setState({
        status: "",
        currentValue,
        value: currentValue
      });
    });
  }

  __handleSave = () => {
    this.setState({ saveStatus: "loading" });
    saveTranslator(
      this.props.translatorLangCode,
      this.props.langKey,
      this.state.value
    )
      .then(() => {
        this.props.onClose();
      })
      .finally(() => {
        this.setState({ saveStatus: "" });
      });
  };

  __handleChangeValue = value => {
    this.setState({
      value
    });
  };

  __handleChangeLanguage = () => {
    openStateModal("language", { byTranslator: true });
  };

  renderContent() {
    const { state, props } = this;

    if (state.status) {
      return (
        <LoadingStatus
          inline
          status={state.status}
          onRetry={() => this.__load()}
        />
      );
    }

    const enLang = getLang(props.langKey, true, "en");
    const disabled = state.currentValue === state.value;
    const langCode = props.translatorLangCode;
    const lang = props.langList.find(l => l.value === langCode);

    return (
      <>
        <div className="Translation__title">Key</div>
        <div className="Translation__key">
          <Clipboard skipIcon text={props.langKey} />
        </div>
        <div className="Translation__title">English</div>
        <div className="Translation__key">{enLang}</div>
        <div
          className="Translation__title Translation__lang"
          onClick={this.__handleChangeLanguage}
        >
          <div>{lang.title} translation</div>
          <span>
            <SVG
              src={require(`asset/site/lang-flags/${this.props.translatorLangCode}.svg`)}
            />
          </span>
        </div>
        <UI.Input
          placeholder={getLang("cabinet__typeAnyText", true)}
          multiLine
          onTextChange={this.__handleChangeValue}
          value={state.value}
          autoFocus={true}
        />
        <div className="Translation__button">
          <UI.Button
            size="large"
            onClick={this.__handleSave}
            state={this.state.saveStatus}
            disabled={disabled}
          >
            {getLang("cabinet_settingsSave", true)}
          </UI.Button>
        </div>
      </>
    );
  }

  render() {
    return (
      <UI.Modal noSpacing isOpen={true} onClose={this.props.onClose}>
        <UI.ModalHeader>Translation</UI.ModalHeader>
        <div className="Translation__wrapper">{this.renderContent()}</div>
      </UI.Modal>
    );
  }
}

export default connect(state => ({
  langList: state.default.langList,
  translator: state.settings.translator,
  translatorLangCode: state.settings.translatorLangCode
}))(TranslatorModal);
