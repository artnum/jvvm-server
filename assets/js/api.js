function API() {
    if (API._instance) { return API._instance }
    API._instance = this
}

API.prototype = {
    fetch(url) {
        return new Promise((resolve ,reject) => {
            fetch(url)
            .then(response => {
                if (!response.ok) { return reject('Error from server') }
                return response.json()
            })
            .then(content => {
                if (!Array.isArray(content)) { return reject('Error from server') }
                if (content[0].__set !== 'start') { return reject('Error from server') }
                const stream_id = content[0].id
                if (
                    stream_id === undefined 
                    || stream_id === null 
                ) {
                    return reject('Error from server')
                }
                const objects = []
                for(let i = 1; i < content.length; i++) {
                    if (content[i].__set === 'end') { break; }
                    if (content[i].__set === 'error') { continue; }
                    if (content[i].__set === 'item') {
                        objects.push(content[i].item)
                    }
                }
                return resolve(objects)
            })
            .catch(reason => {
                return reject(reason)
            })
        })
    }
}