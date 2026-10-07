// e2e.js and admin-e2e.js submit forms, edit content and empty the visit log, so they must
// only ever run against a throw-away site. Refuse anything that does not look like one.
module.exports = function guard(base) {
    const host = new URL(base).hostname;
    const local = /^(localhost|127\.\d+\.\d+\.\d+|\[::1\])$|\.(test|local|localhost|invalid)$/.test(host);

    if (!local && process.env.EPIC_TESTS_MAY_WRITE !== 'yes') {
        console.error(`Refusing to run against ${host}: these tests write data. Use a local or staging copy, or set EPIC_TESTS_MAY_WRITE=yes if this really is a throw-away site.`);
        process.exit(2);
    }
};
