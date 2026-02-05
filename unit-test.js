// test-api.js
const API_URL = 'http://localhost/schoolopdrachten/project-beer-casus/api/bier';

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

function assertNotNull(value, message) {
    return assert(value !== null && value !== undefined, message);
}

async function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

// ============= TEST FUNCTIES =============

// GET - Alle bieren
async function testGetAllBeers() {
    console.log(`\n${colors.blue}=== TEST: GET alle bieren ===${colors.reset}`);
    
    try {
        const response = await fetch(API_URL);
        const data = await response.json();
        
        assertEqual(response.status, 200, 'Status code moet 200 zijn');
        assert(Array.isArray(data), 'Response moet een array zijn');
        console.log(`   ${colors.cyan}Aantal bieren: ${data.length}${colors.reset}`);
        
        return data;
    } catch (error) {
        assert(false, `GET alle bieren gefaald: ${error.message}`);
        return [];
    }
}

// GET - Enkel bier
async function testGetSingleBeer(id) {
    console.log(`\n${colors.blue}=== TEST: GET enkel bier (ID: ${id}) ===${colors.reset}`);
    
    try {
        const response = await fetch(`${API_URL}/${id}`);
        const data = await response.json();
        
        assertEqual(response.status, 200, 'Status code moet 200 zijn');
        assertNotNull(data.id, 'Bier moet een ID hebben');
        assertEqual(data.id, id, 'ID moet overeenkomen');
        assertNotNull(data.naam, 'Bier moet een naam hebben');
        assertNotNull(data.merk, 'Bier moet een merk hebben');
        
        console.log(`   ${colors.cyan}Bier: ${data.naam} (${data.merk})${colors.reset}`);
        return data;
    } catch (error) {
        assert(false, `GET enkel bier gefaald: ${error.message}`);
        return null;
    }
}

// GET - Niet-bestaand bier (404)
async function testGetNonExistentBeer() {
    console.log(`\n${colors.blue}=== TEST: GET niet-bestaand bier (404) ===${colors.reset}`);
    
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

// POST - Nieuw bier aanmaken
async function testCreateBeer() {
    console.log(`\n${colors.blue}=== TEST: POST nieuw bier aanmaken ===${colors.reset}`);
    
    const newBeer = {
        naam: 'Unit Test Bier',
        brouwer: 'Test Brouwerij',
        type: 'IPA',
        perc: 6.5,
        inkoop_prijs: 2.50
    };
    
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(newBeer)
        });
        const data = await response.json();
        
        assertEqual(response.status, 201, 'Status code moet 201 zijn (Created)');
        assertNotNull(data.id, 'Response moet een ID bevatten');
        assertEqual(data.message, 'Bier toegevoegd', 'Correct bericht verwacht');
        
        console.log(`   ${colors.cyan}Nieuw bier ID: ${data.id}${colors.reset}`);
        return data.id;
    } catch (error) {
        assert(false, `POST nieuw bier gefaald: ${error.message}`);
        return null;
    }
}

// POST - Ongeldige data (400)
async function testCreateBeerInvalidData() {
    console.log(`\n${colors.blue}=== TEST: POST met ongeldige data (400) ===${colors.reset}`);
    
    const invalidBeer = {
        // naam en merk ontbreken (verplicht)
        type: 'Lager',
        alcohol: 5.0
    };
    
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(invalidBeer)
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

// PUT - Bier updaten
async function testUpdateBeer(id) {
    console.log(`\n${colors.blue}=== TEST: PUT bier updaten (ID: ${id}) ===${colors.reset}`);
    
    const updatedBeer = {
        naam: 'Unit Test Bier UPDATED',
        merk: 'Test Brouwerij UPDATED',
        type: 'Double IPA',
        alcohol: 8.5,
        prijs: 3.50
    };
    
    try {
        const response = await fetch(`${API_URL}/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(updatedBeer)
        });
        const data = await response.json();
        
        assertEqual(response.status, 200, 'Status code moet 200 zijn');
        assertEqual(data.message, 'Bier geüpdatet', 'Correct bericht verwacht');
        
        // Verifieer dat de update gelukt is
        await sleep(100);
        const beer = await testGetSingleBeer(id);
        if (beer) {
            assertEqual(beer.naam, 'Unit Test Bier UPDATED', 'Naam moet geüpdatet zijn');
            assertEqual(parseFloat(beer.alcohol), 8.5, 'Alcohol percentage moet geüpdatet zijn');
            console.log(`   ${colors.cyan}✓ Update geverifieerd${colors.reset}`);
        }
        
        return true;
    } catch (error) {
        assert(false, `PUT update bier gefaald: ${error.message}`);
        return false;
    }
}

// PUT - Niet-bestaand bier updaten (404)
async function testUpdateNonExistentBeer() {
    console.log(`\n${colors.blue}=== TEST: PUT niet-bestaand bier (404) ===${colors.reset}`);
    
    const beer = {
        naam: 'Test',
        merk: 'Test',
        type: 'Lager',
        alcohol: 5.0,
        prijs: 1.50
    };
    
    try {
        const response = await fetch(`${API_URL}/99999`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(beer)
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

// DELETE - Bier verwijderen
async function testDeleteBeer(id) {
    console.log(`\n${colors.blue}=== TEST: DELETE bier (ID: ${id}) ===${colors.reset}`);
    
    try {
        const response = await fetch(`${API_URL}/${id}`, {
            method: 'DELETE'
        });
        const data = await response.json();
        
        assertEqual(response.status, 200, 'Status code moet 200 zijn');
        assertEqual(data.message, 'Bier verwijderd', 'Correct bericht verwacht');
        
        console.log(`   ${colors.cyan}Bier ${id} verwijderd${colors.reset}`);
        
        // Verifieer dat bier echt verwijderd is
        await sleep(100);
        const checkResponse = await fetch(`${API_URL}/${id}`);
        assertEqual(checkResponse.status, 404, 'Bier moet niet meer bestaan (404)');
        console.log(`   ${colors.cyan}✓ Verwijdering geverifieerd${colors.reset}`);
        
        return true;
    } catch (error) {
        assert(false, `DELETE bier gefaald: ${error.message}`);
        return false;
    }
}

// DELETE - Niet-bestaand bier verwijderen (404)
async function testDeleteNonExistentBeer() {
    console.log(`\n${colors.blue}=== TEST: DELETE niet-bestaand bier (404) ===${colors.reset}`);
    
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

// DELETE - Meerdere bieren in reeks
async function testDeleteMultipleBeers() {
    console.log(`\n${colors.blue}=== TEST: DELETE meerdere bieren ===${colors.reset}`);
    
    // Maak 3 test bieren
    const ids = [];
    for (let i = 0; i < 3; i++) {
        const beer = {
            naam: `Delete Test ${i + 1}`,
            merk: 'Delete Test Brouwerij',
            type: 'Test',
            alcohol: 5.0,
            prijs: 1.00
        };
        
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(beer)
        });
        const data = await response.json();
        ids.push(data.id);
        await sleep(50);
    }
    
    console.log(`   ${colors.cyan}Aangemaakt: ${ids.length} test bieren${colors.reset}`);
    
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
    
    assertEqual(deletedCount, 3, 'Alle 3 bieren moeten verwijderd zijn');
    console.log(`   ${colors.cyan}Verwijderd: ${deletedCount} bieren${colors.reset}`);
    
    return true;
}

// ============= HOOFDTEST FUNCTIE =============

async function runAllTests() {
    console.log(`${colors.yellow}╔════════════════════════════════════════╗${colors.reset}`);
    console.log(`${colors.yellow}║   REST API UNIT TESTS - BIER API      ║${colors.reset}`);
    console.log(`${colors.yellow}║         Inclusief DELETE Tests        ║${colors.reset}`);
    console.log(`${colors.yellow}╚════════════════════════════════════════╝${colors.reset}`);
    
    let createdBeerId = null;
    
    try {
        // ===== GET TESTS =====
        console.log(`\n${colors.yellow}▶ GET TESTS${colors.reset}`);
        await testGetAllBeers();
        await sleep(100);
        
        await testGetNonExistentBeer();
        await sleep(100);
        
        // ===== POST TESTS =====
        console.log(`\n${colors.yellow}▶ POST TESTS${colors.reset}`);
        createdBeerId = await testCreateBeer();
        await sleep(100);
        
        await testCreateBeerInvalidData();
        await sleep(100);
        
        // ===== GET SINGLE TESTS =====
        console.log(`\n${colors.yellow}▶ GET SINGLE TESTS${colors.reset}`);
        if (createdBeerId) {
            await testGetSingleBeer(createdBeerId);
            await sleep(100);
        }
        
        // ===== PUT TESTS =====
        console.log(`\n${colors.yellow}▶ PUT TESTS${colors.reset}`);
        if (createdBeerId) {
            await testUpdateBeer(createdBeerId);
            await sleep(100);
        }
        
        await testUpdateNonExistentBeer();
        await sleep(100);
        
        // ===== DELETE TESTS =====
        console.log(`\n${colors.yellow}▶ DELETE TESTS${colors.reset}`);
        
        await testDeleteNonExistentBeer();
        await sleep(100);
        
        await testDeleteWithoutId();
        await sleep(100);
        
        await testDeleteMultipleBeers();
        await sleep(100);
        
        // DELETE het eerder aangemaakte bier als laatste test
        if (createdBeerId) {
            await testDeleteBeer(createdBeerId);
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