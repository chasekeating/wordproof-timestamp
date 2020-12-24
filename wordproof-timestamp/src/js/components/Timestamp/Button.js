import React, {Component} from 'react';
import axios from 'axios';
import qs from 'qs';

export default class Button extends Component {

    constructor(props) {
        super(props);

        this.state = {
            post: this.props.post,
            action: this.props.action,
            loading: this.props.loading,
            disabled: false,
        };
    }

    componentDidUpdate(prevProps) {
        if (prevProps.loading !== this.props.loading)
            this.setState({loading: this.props.loading});
    }

    getRetryWebhookButton() {
        // let now = Math.ceil(Date.now() / 1000);
        // let lastTimestamped = parseInt(this.state.meta.timestampedOn) + 20;
        // if (!this.state.meta.timestampedOn || now > lastTimestamped) {
        return (
            <button className={'button block'} disabled={this.state.disabled}
                    onClick={(e) => this.request(e, 'wordproof_wsfy_retry_webhook')}>Request new webhook</button>
        );
    }

    getTimestampButton() {
        return (
            <button className={'button block'} disabled={this.state.disabled}
                    onClick={(e) => this.request(e, 'wordproof_wsfy_save_post')}>Timestamp
                this {this.state.post.type}</button>
        );
    }

    getButton = () => {
        if (this.state.loading) {
            return '';
        }
        if (this.state.action === 'retry') {
            return this.getRetryWebhookButton();
        } else {
            return this.getTimestampButton();
        }
    }

    async request(event, action) {
        event.preventDefault();
        this.setState({disabled: true});

        const response = await axios.post(wordproofData.urls.ajax, qs.stringify({
            'action': action,
            'post_id': this.state.post.id,
            'security': wordproofData.ajaxSecurity
        }));

        const message = Button.retrieveMessage(response, this.state.post.type);
        this.props.callback(message.reply, (message.success) ? message.success : false);
    }

    static retrieveMessage(result, postType) {

        if (typeof result === 'string')
            result = JSON.parse(result);

        if (result.data && typeof result.data === 'string')
            result.data = JSON.parse(result.data);

        if (result.data.errors && typeof result.data.errors === 'object') {
            if ('is_duplicate' in result.data.errors)
                return { reply: <span>🚚 This article already exists on the server</span>};

            if ('webhook_failed' in result.data.errors)
                return { reply: <span>🤭 The webhook failed</span> };

            if ('content' in result.data.errors)
                return { reply: <span>✍️ Add some content first!</span> };

            if ('cron_scheduled' in result.data.errors)
                return { reply: <span>⏰ This article is already scheduled to be timestamped</span> };
        }

        if (result.data.errors)
            return { reply: <span>🤭 Something went wrong. {JSON.stringify(result.errors)}</span> };

        if (result.data) {
            if (result.data.message === 'Unauthenticated.')
                return { reply: <span>🔐 <a href={wordproofData.urls.wizardConnect}>Please check if your Site Key if present and valid</a></span> };

            if (result.data.success) {
                return { reply: <span>👍 {Button.uppercase(postType)} is sent to My WordProof</span>, success: true };
            }
        }

        return { reply: <span>🤭 Something went wrong.</span> };
    }

    static uppercase(string) {
        return string[0].toUpperCase() + string.substring(1)
    }

    render() {
        { return this.getButton() }
    }
}

