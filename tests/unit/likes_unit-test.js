// test-api.js
const API_URL = 'http://project-beer.local/api/likes';

// Kleuren voor console output
const colors = {
    green: '\x1b[32m',
    red: '\x1b[31m',
    yellow: '\x1b[33m',
    blue: '\x1b[34m',
    cyan: '\x1b[36m',
    reset: '\x1b[0m'
};

// Test resultaten
let testsRun = 0;
let testsPassed = 0;
let testsFailed = 0;

// Helper functies
function assert(condition, message) {
    testsRun++;
    if (condition) {
        testsPassed++;
        console.log(`${colors.green}✓ PASS${colors.reset}: ${message}`);
        return true;
    } else {
        testsFailed++;
        console.log(`${colors.red}✗ FAIL${colors.reset}: ${message}`);
        return false;
    }
}

function assertEqual(actual, expected, message) {
    return assert(actual === expected, `${message} (verwacht: ${expected}, gekregen: ${actual})`);
}


function assertOneEqual(actual, expected, message) {

    return assert(expected.includes(actual), `${message} (verwacht: ${expected.join(' or ')}, gekregen: ${actual})`);
}

function assertNotNull(value, message) {
    return assert(value !== null && value !== undefined, message);
}

async function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

// ============= TEST FUNCTIES =============

// GET - Alle likes
async function testGetAllLikes() {
    console.log(`\n${colors.blue}=== TEST: GET alle likes ===${colors.reset}`);
    
    try {
        const response = await fetch(API_URL);
        const data = await response.json();
        
        assertEqual(response.status, 200, 'Status code moet 200 zijn');
        assert(Array.isArray(data), 'Response moet een array zijn');
    
        // Verifieer dat de structuur klopt
        assertNotNull(data[0].beer_id, 'Like moet een beer_id hebben');
        
        console.log(`   ${colors.cyan}Aantal likes: ${data.length}${colors.reset}`);
        
        return data;
    } catch (error) {
        assert(false, `GET alle likes gefaald: ${error.message}`);
        return [];
    }
}

// GET - Enkel like
async function testGetSingleLike(id) {
    console.log(`\n${colors.blue}=== TEST: GET enkel like (ID: ${id}) ===${colors.reset}`);
    
    try {
        const response = await fetch(`${API_URL}/${id}`);
        const data = await response.json();
        
        assertEqual(response.status, 200, 'Status code moet 200 zijn');
        assertNotNull(data.beer_id, 'Like moet een beer_id hebben');
        
        console.log(`   ${colors.cyan}Like: ${data.beer_id}${colors.reset}`);
        return data;
    } catch (error) {
        assert(false, `GET enkel like gefaald: ${error.message}`);
        return null;
    }
}

// GET - Niet-bestaand like (404)
async function testGetNonExistentLike() {
    console.log(`\n${colors.blue}=== TEST: GET niet-bestaand like (404) ===${colors.reset}`);
    
    try {
        const response = await fetch(`${API_URL}/99999`);
        const data = await response.json();
        
        assertEqual(response.status, 404, 'Status code moet 404 zijn');
        assertNotNull(data.error, 'Error bericht moet aanwezig zijn');
        
        return true;
    } catch (error) {
        assert(false, `Test gefaald: ${error.message}`);
        return false;
    }
}

// POST - Nieuw like aanmaken
async function testCreateLike() {
    console.log(`\n${colors.blue}=== TEST: POST nieuw like aanmaken ===${colors.reset}`);
    
    // Example data for a new like (this beer must exist in the database)
    const newLike = {
        beer_id: 1
    };
    
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(newLike)
        });
        const data = await response.json();

        delete data.new_resource.id;
        
        assertEqual(response.status, 201, 'Status code moet 201 zijn (Created)');
        assertNotNull(data.id, 'Response moet een ID bevatten');
        assertEqual(data.message, 'Added resource successfully', 'Correct bericht verwacht');
        
        console.log(`${colors.cyan}Nieuw like ID: ${data.id}${colors.reset}`);
        return data.id;
    } catch (error) {
        assert(false, `POST nieuw like gefaald: ${error.message}`);
        return null;
    }
}

// POST - Ongeldige data (400)
async function testCreateLikeInvalidData() {
    console.log(`\n${colors.blue}=== TEST: POST met ongeldige data (400) ===${colors.reset}`);
    
    const invalidLike = {
        // beer_id ontbreekt (verplicht)
        wrong: 'data'
    };
    
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(invalidLike)
        });
        const data = await response.json();
        
        assertEqual(response.status, 400, 'Status code moet 400 zijn (Bad Request)');
        assertNotNull(data.error, 'Error bericht moet aanwezig zijn');
        assert(
            data.error.includes('verplicht') || data.error.includes('required'),
            'Error moet vermelden dat velden verplicht zijn'
        );
        
        return true;
    } catch (error) {
        assert(false, `Test gefaald: ${error.message}`);
        return false;
    }
}

// PUT - Like updaten
async function testUpdateLike(id) {
    console.log(`\n${colors.blue}=== TEST: PUT like updaten (ID: ${id}) ===${colors.reset}`);
    
    const updatedLike = {
        beer_id: 1
    };
    
    try {
        const response = await fetch(`${API_URL}/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(updatedLike)
        });
        const data = await response.json();
        
        assertEqual(response.status, 200, 'Status code moet 200 zijn');
        assertOneEqual(data.message, ['Updated resource successfully', 'No changes were made to the resource'] , 'Correct bericht verwacht');
        
        // Verifieer dat de update gelukt is
        await sleep(100);
        const like = await testGetSingleLike(id);
        if (like) {
            assertEqual(like.beer_id, 1, 'beer_id moet geüpdatet zijn');
            console.log(`   ${colors.cyan}✓ Update geverifieerd${colors.reset}`);
        }
        
        return true;
    } catch (error) {
        assert(false, `PUT update like gefaald: ${error.message}`);
        return false;
    }
}

// PUT - Niet-bestaand like updaten (404)
async function testUpdateNonExistentLike() {
    console.log(`\n${colors.blue}=== TEST: PUT niet-bestaand like (404) ===${colors.reset}`);
    
    const like = {
        beer_id: 1
    };
    
    try {
        const response = await fetch(`${API_URL}/99999`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(like)
        });
        const data = await response.json();
        
        assertEqual(response.status, 404, 'Status code moet 404 zijn');
        assertNotNull(data.error, 'Error bericht moet aanwezig zijn');
        
        return true;
    } catch (error) {
        assert(false, `Test gefaald: ${error.message}`);
        return false;
    }
}

// DELETE - Like verwijderen
async function testDeleteLike(id) {
    console.log(`\n${colors.blue}=== TEST: DELETE like (ID: ${id}) ===${colors.reset}`);
    
    try {
        const response = await fetch(`${API_URL}/${id}`, {
            method: 'DELETE'
        });
        const data = await response.json();
        
        assertEqual(response.status, 200, 'Status code moet 200 zijn');
        assertEqual(data.message, 'This resource is deleted', 'Correct bericht verwacht');
        
        console.log(`   ${colors.cyan}Like ${id} verwijderd${colors.reset}`);
        
        // Verifieer dat like echt verwijderd is
        await sleep(100);
        const checkResponse = await fetch(`${API_URL}/${id}`);
        assertEqual(checkResponse.status, 404, 'Like moet niet meer bestaan (404)');
        console.log(`   ${colors.cyan}✓ Verwijdering geverifieerd${colors.reset}`);
        
        return true;
    } catch (error) {
        assert(false, `DELETE like gefaald: ${error.message}`);
        return false;
    }
}

// DELETE - Niet-bestaand like verwijderen (404)
async function testDeleteNonExistentLike() {
    console.log(`\n${colors.blue}=== TEST: DELETE niet-bestaand like (404) ===${colors.reset}`);
    
    try {
        const response = await fetch(`${API_URL}/99999`, {
            method: 'DELETE'
        });
        const data = await response.json();
        
        assertEqual(response.status, 404, 'Status code moet 404 zijn');
        assertNotNull(data.error, 'Error bericht moet aanwezig zijn');
        
        return true;
    } catch (error) {
        assert(false, `Test gefaald: ${error.message}`);
        return false;
    }
}

// DELETE - Zonder ID (400)
async function testDeleteWithoutId() {
    console.log(`\n${colors.blue}=== TEST: DELETE zonder ID (400) ===${colors.reset}`);
    
    try {
        const response = await fetch(API_URL, {
            method: 'DELETE'
        });
        const data = await response.json();
        
        assertEqual(response.status, 400, 'Status code moet 400 zijn (Bad Request)');
        assertNotNull(data.error, 'Error bericht moet aanwezig zijn');
        
        return true;
    } catch (error) {
        assert(false, `Test gefaald: ${error.message}`);
        return false;
    }
}

// DELETE - Meerdere likes in reeks
async function testDeleteMultipleLikes() {
    console.log(`\n${colors.blue}=== TEST: DELETE meerdere likes ===${colors.reset}`);
    
    // Maak 3 test likes
    const ids = [];
    for (let i = 0; i < 3; i++) {
        const like = {
            beer_id: `${i + 1}`,
        };
        
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(like)
        });
        const data = await response.json();
        ids.push(data.id);
        await sleep(50);
    }
    
    console.log(`   ${colors.cyan}Aangemaakt: ${ids.length} test likes${colors.reset}`);
    
    // Verwijder ze allemaal
    let deletedCount = 0;
    for (const id of ids) {
        const response = await fetch(`${API_URL}/${id}`, {
            method: 'DELETE'
        });
        if (response.status === 200) {
            deletedCount++;
        }
        await sleep(50);
    }
    
    assertEqual(deletedCount, 3, 'Alle 3 likes moeten verwijderd zijn');
    console.log(`   ${colors.cyan}Verwijderd: ${deletedCount} likes${colors.reset}`);
    
    return true;
}

// ============= HOOFDTEST FUNCTIE =============

async function runAllTests() {
    console.log(`${colors.yellow}╔════════════════════════════════════════╗${colors.reset}`);
    console.log(`${colors.yellow}║   REST API UNIT TESTS - LIKE API      ║${colors.reset}`);
    console.log(`${colors.yellow}║         Inclusief DELETE Tests        ║${colors.reset}`);
    console.log(`${colors.yellow}╚════════════════════════════════════════╝${colors.reset}`);
    
    let createdLikeId = null;
    
    try {
        // ===== GET TESTS =====
        console.log(`\n${colors.yellow}▶ GET TESTS${colors.reset}`);
        await testGetAllLikes();
        await sleep(100);
        
        await testGetNonExistentLike();
        await sleep(100);
        
        // ===== POST TESTS =====
        console.log(`\n${colors.yellow}▶ POST TESTS${colors.reset}`);
        createdLikeId = await testCreateLike();
        await sleep(100);
        
        await testCreateLikeInvalidData();
        await sleep(100);
        
        // ===== GET SINGLE TESTS =====
        console.log(`\n${colors.yellow}▶ GET SINGLE TESTS${colors.reset}`);
        if (createdLikeId) {
            await testGetSingleLike(createdLikeId);
            await sleep(100);
        }
        
        // ===== PUT TESTS =====
        console.log(`\n${colors.yellow}▶ PUT TESTS${colors.reset}`);
        if (createdLikeId) {
            await testUpdateLike(createdLikeId);
            await sleep(100);
        }
        
        await testUpdateNonExistentLike();
        await sleep(100);
        
        // ===== DELETE TESTS =====
        console.log(`\n${colors.yellow}▶ DELETE TESTS${colors.reset}`);
        
        await testDeleteNonExistentLike();
        await sleep(100);
        
        await testDeleteWithoutId();
        await sleep(100);
        
        await testDeleteMultipleLikes();
        await sleep(100);
        
        // DELETE het eerder aangemaakte like als laatste test
        if (createdLikeId) {
            await testDeleteLike(createdLikeId);
        }
        
    } catch (error) {
        console.log(`${colors.red}FOUT: ${error.message}${colors.reset}`);
    }
    
    // ===== RESULTATEN =====
    console.log(`\n${colors.yellow}╔════════════════════════════════════════╗${colors.reset}`);
    console.log(`${colors.yellow}║           TEST RESULTATEN              ║${colors.reset}`);
    console.log(`${colors.yellow}╚════════════════════════════════════════╝${colors.reset}`);
    console.log(`Totaal tests:  ${testsRun}`);
    console.log(`${colors.green}Geslaagd:      ${testsPassed}${colors.reset}`);
    console.log(`${colors.red}Gefaald:       ${testsFailed}${colors.reset}`);
    
    const percentage = testsRun > 0 ? ((testsPassed / testsRun) * 100).toFixed(1) : 0;
    console.log(`Slagingspercentage: ${percentage}%`);
    
    if (testsFailed === 0) {
        console.log(`\n${colors.green}╔════════════════════════════════════════╗${colors.reset}`);
        console.log(`${colors.green}║   🎉 ALLE TESTS GESLAAGD! 🎉          ║${colors.reset}`);
        console.log(`${colors.green}╚════════════════════════════════════════╝${colors.reset}\n`);
    } else {
        console.log(`\n${colors.red}╔════════════════════════════════════════╗${colors.reset}`);
        console.log(`${colors.red}║  ⚠️  ${testsFailed} TEST(S) GEFAALD ⚠️             ║${colors.reset}`);
        console.log(`${colors.red}╚════════════════════════════════════════╝${colors.reset}\n`);
    }
}

// Run tests
runAllTests();