function find_node(node, query, result) {
    if (node.hasChildNodes() && !result) { 
        // NOTE: 'var' must exist before the 'i' here... otherwise - this recursive function will enter an infinite loop.
        for (var i = 0; i < node.childNodes.length; i++) {
            if (node.childNodes[i].nodeType == 1) {
                if (node.childNodes[i].getAttribute('id') == query || node.childNodes[i].getAttribute('name') == query || node.childNodes[i].className == query) {
                    result = node.childNodes[i];
                    return result;
                } else {
                    result = find_node(node.childNodes[i], query);
                    if (result) {
                        return result;
                    }
                }
            }
        }
    }
    return result;
}
