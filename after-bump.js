/*
 * Copyright 2021 LABOR.digital
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * Last modified: 2021.06.27 at 21:19
 */
// This helper is used to automatically set the version in the ext_emconf.php file to the latest version
// after the conventional release generated a new number for us
const fs = require('fs');
const path = require('path');
const version = process.argv[2];

function updateEmConfVersion(filename, version)
{
    let content = fs.readFileSync(filename).toString('utf-8');
    content = content.replace(/(["']version["']\s+=>\s+)(["'].*?["'])/, '$1"' + version + '"');
    fs.writeFileSync(filename, content);
}

updateEmConfVersion(path.join(__dirname, 'ext_emconf.php'), version);