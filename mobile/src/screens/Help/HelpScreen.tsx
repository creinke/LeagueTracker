import React from 'react';
import {ScrollView, View, Text, StyleSheet} from 'react-native';

export default function HelpScreen() {
    return (
        <ScrollView style={styles.container}>
            <View style={styles.content}>
                <Text style={styles.title}>League Tracker Help</Text>
                
                <Text style={styles.sectionTitle}>Overview</Text>
                <Text style={styles.text}>
                    Welcome to League Tracker Mobile. This app allows you to view players, seasons, and events, 
                    as well as enter scores and view results.
                </Text>

                <Text style={styles.sectionTitle}>Event Shortcuts</Text>
                <Text style={styles.text}>
                    • Display Next Event Pairings: Shows the game schedule and pairings for the upcoming event.
                    • Post Last Event Scores: Allows entering or editing scores for the most recent event.
                    • Display Last Event Results: Shows the finalized rankings and scores for the most recent event.
                </Text>

                <Text style={styles.sectionTitle}>Scoring Rules</Text>
                <Text style={styles.text}>
                    Scores are entered hole-by-hole. The app supports various formats including Singles Stroke Play, 
                    Match Play, and Team Events (Scrambles).
                </Text>

                <Text style={styles.sectionTitle}>Substitutions</Text>
                <Text style={styles.text}>
                    If a player is missing, use the "Substitute Players" button on the Score Entry screen 
                    to replace them with another player from the roster.
                </Text>
            </View>
        </ScrollView>
    );
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: '#fff',
    },
    content: {
        padding: 20,
    },
    title: {
        fontSize: 24,
        fontWeight: 'bold',
        marginBottom: 20,
        textAlign: 'center',
    },
    sectionTitle: {
        fontSize: 18,
        fontWeight: 'bold',
        marginTop: 20,
        marginBottom: 10,
        color: '#2c3e50',
    },
    text: {
        fontSize: 16,
        lineHeight: 24,
        color: '#34495e',
    },
});
