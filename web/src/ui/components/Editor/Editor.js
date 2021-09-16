import "./Editor.less";
import React from "react";
import {
  Editor as DraftEditor,
  EditorState,
  RichUtils,
  convertFromHTML,
  CompositeDecorator,
  convertFromRaw,
  ContentState,
  convertToRaw
} from "draft-js";
import EditorTooltip from "../EditorTooltip/EditorTooltip";
import { Code } from "../../index";
import { classNames as cn } from "../../utils/index";

export default class Editor extends React.Component {
  componentWillMount() {
    return true;
  }

  constructor(props) {
    super(props);
    this.state = {
      editorState: this.prepareState(props.content),
      hide: true,
      focus: false,
      rect: {
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
        width: 0,
        height: 0
      }
    };

    this.onChange = editorState => this.setState({ editorState });
    this.update = this.update.bind(this);
    this.handleKeyCommand = this.handleKeyCommand.bind(this);
    this.setLink = this._setLink.bind(this);
  }

  prepareState = content => {
    const decorator = new CompositeDecorator([
      {
        strategy: findLinkEntities,
        component: Link
      }
    ]);

    if (content) {
      if (typeof content === "object") {
        if (this.props.short) {
          const block = content.blocks.filter(b => b.type === "unstyled")[0];
          return EditorState.createWithContent(
            convertFromRaw({
              ...content,
              blocks: block ? [block] : []
            }),
            decorator
          );
        }
        return EditorState.createWithContent(
          convertFromRaw(content),
          decorator
        );
      } else {
        const blocksFromHTML = convertFromHTML(content);
        const state = ContentState.createFromBlockArray(
          blocksFromHTML.contentBlocks,
          blocksFromHTML.entityMap
        );

        // const decorator = new CompositeDecorator([]);
        return EditorState.createWithContent(state, decorator);
      }
    } else {
      return EditorState.createEmpty(decorator);
    }
  };

  blockRendererFn = contentBlock => {
    const type = contentBlock.getType();
    const text = contentBlock.getText();

    if (type === "code") {
      return {
        component: props => <Code>{props.blockProps.text}</Code>,
        editable: false,
        props: {
          text
        }
      };
    }
    // if (type === 'unstyled') {
    //   return {
    //     component: props => <p>{props.blockProps.text}</p>,
    //     props: { text }
    //   };
    // }
  };

  _setLink(e) {
    e.preventDefault();
    const { editorState } = this.state;
    const contentState = editorState.getCurrentContent();
    const contentStateWithEntity = contentState.createEntity(
      "LINK",
      "MUTABLE",
      { url: window.prompt("Enter url") }
    );
    const entityKey = contentStateWithEntity.getLastCreatedEntityKey();
    const newEditorState = EditorState.set(editorState, {
      currentContent: contentStateWithEntity
    });
    this.setState(
      {
        editorState: RichUtils.toggleLink(
          newEditorState,
          newEditorState.getSelection(),
          entityKey
        ),
        showURLInput: false,
        urlValue: ""
      },
      () => {
        setTimeout(() => this.refs.editor.focus(), 0);
      }
    );
  }

  update() {
    let selection = document.getSelection();
    this.range = selection && selection.rangeCount && selection.getRangeAt(0);
    this.updateRect(this.range.startOffset === this.range.endOffset);
  }

  handleKeyCommand(command, editorState) {
    const newState = RichUtils.handleKeyCommand(editorState, command);
    if (newState) {
      this.onChange(newState);
      return "handled";
    }
    return "not-handled";
  }

  updateRect(hide) {
    let rect = {
      top: 0,
      left: 0,
      right: 0,
      bottom: 0,
      width: 0,
      height: 0
    };

    if (!hide && this.range) {
      const currentRect = this.range.getBoundingClientRect();
      const editorRect = this.refs.editor.getBoundingClientRect();

      rect = {
        ...rect,
        width: currentRect.width,
        height: currentRect.height,
        top: currentRect.top - editorRect.top,
        left: currentRect.left - editorRect.left
      };
    }

    this.setState({ rect, hide: !!hide });
    // console.log(this.state.rect);
  }

  handleChange = editorState => {
    let selection = editorState.getSelection();

    this.update();
    this.setState({ focus: selection.hasFocus });

    this.onChange(editorState);
    this.props.onChange &&
      this.props.onChange(convertToRaw(editorState.getCurrentContent()));
  };

  render() {
    const style = {
      transform: `translate(calc(-50% + ${this.state.rect.left +
        this.state.rect.width / 2}px), calc(-100% + ${this.state.rect.top -
        10}px))`
    };

    return (
      <div
        className={cn("Editor", this.props.className, {
          border: this.props.border,
          focus: this.state.focus
        })}
        ref="editor"
      >
        <div className="Editor__wrapper">
          <DraftEditor
            customStyleMap={styleMap}
            ref="editor"
            readOnly={this.props.readOnly}
            blockRendererFn={this.blockRendererFn}
            handleKeyCommand={this.handleKeyCommand}
            editorState={
              this.props.autoUpdate
                ? this.prepareState(this.props.content)
                : this.state.editorState
            }
            onChange={this.handleChange}
          />
          {!this.props.readOnly && (
            <EditorTooltip
              onToggleBlockType={type => {
                this.onChange(
                  RichUtils.toggleBlockType(this.state.editorState, type)
                );
              }}
              onToggleInlineStyle={type => {
                this.onChange(
                  RichUtils.toggleInlineStyle(this.state.editorState, type)
                );
              }}
              onSetLint={this.setLink}
              visible={!this.state.hide}
              style={style}
            />
          )}
          {/*<div className="Editor__shape" style={{ ...this.state.rect }} />*/}
        </div>
        {/*<pre>{JSON.stringify(convertToRaw(this.state.editorState.getCurrentContent()), null, 2)}</pre>*/}
      </div>
    );
  }
}

const styleMap = {
  CODE: {
    backgroundColor: "#f8f8f8",
    padding: "2px 4px",
    fontFamily: "monospace",
    fontWeight: "500",
    border: `1px solid var('--light-gray')`
  }
};

function findLinkEntities(contentBlock, callback, contentState) {
  contentBlock.findEntityRanges(character => {
    const entityKey = character.getEntity();
    return (
      entityKey !== null &&
      contentState.getEntity(entityKey).getType() === "LINK"
    );
  }, callback);
}

const Link = props => {
  const { url } = props.contentState.getEntity(props.entityKey).getData();
  return (
    <a className="Link" href={url}>
      {props.children}
    </a>
  );
};
