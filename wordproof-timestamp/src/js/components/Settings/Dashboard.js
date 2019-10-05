import React, {Component} from 'react'

export default class Dashboard extends Component {
    constructor(props) {
        super(props)
        this.state = {}
    }

    handleWindowPopup = (event, url) => {
        event.preventDefault();
        window.open(
            url,
            'popUpWindow',
            'height=600,width=900,left=50,top=50,resizable=yes,scrollbars=yes,toolbar=yes,menubar=no,location=no,directories=no,status=yes'
        );
    }

    render() {
        return (
            <div>
                <div className="vo-card vo-columns">
                    <div className="vo-col">
                        <h3>Welcome to WordProof Timestamp</h3>
                        <p>The WordProof Timestamp for WordPress plugin lets you timestamp content on the blockchain.
                            You can
                            either timestamp content manually (using your own blockchain account and wallet) or
                            automatically.</p>

                        <h3>Mode</h3>
                        <p>You can either timestamp content manually (using your own blockchain account and wallet) or
                            automatically.</p>


                        {wordproofSettings.isWSFYActive
                            ? <div>
                                <p>🎉 You are automatically timestamping your content!</p>
                                <a className="button is-primary" href={wordproofSettings.urls.wizard}>Click here if you
                                    want to restart the Setup Wizard</a>
                            </div>
                            : wordproofSettings.network
                                ? <div>
                                    <p>🙌 You&apos;ve configured WordProof to timestamp manually</p>
                                    <a className="button is-primary" href={wordproofSettings.urls.wizard}>Click here if
                                        you want to restart the Setup Wizard</a>
                                </div>
                                : <div>
                                    <p>❌ Let&apos;s get you going quickly. Launch the Setup Wizard to get started.</p>
                                    <a className="button is-primary" href={wordproofSettings.urls.wizard}>Launch
                                        Wizard</a>
                                </div>
                        }

                        <h3>Need some help?</h3>
                        <p>We want to help you if you have any problems!</p>
                        <ul>
                            <li><a href="https://wordproof.io/guide" target="_blank" rel="noopener noreferrer">How to:
                                WordProof Timestamp Setup Guide</a></li>
                            <li><a href={'https://wordpress.org/support/plugin/wordproof-timestamp/'} target="_blank"
                                   rel="noopener noreferrer">Post a question on the WordPress forum</a></li>
                            <li><a href="https://t.me/joinchat/DVuJAEfhf2QURBBjOWc2XA" target="_blank"
                                   rel="noopener noreferrer">Join our Telegram User Group</a></li>
                        </ul>
                        <p>For other inquiries, <a href="mailto:info@wordproof.io" target="_blank"
                                                   rel="noopener noreferrer">Send an email</a>.</p>


                    </div>
                    <div className="vo-col">
                        {wordproofSettings.isWSFYActive
                        &&
                        <iframe width="560" height="315" src="https://www.youtube.com/embed/So_iNDb15-s" frameBorder="0"
                                allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
                                allowFullScreen></iframe>
                        }
                    </div>
                </div>
            </div>
        )
    }
}